<?php
// ============================================================
//  recommend.php — Système de recommandation par CONTENU
//
//  Approche : Content-Based Filtering
//  ─────────────────────────────────────────────────────────
//  1. Profil utilisateur : pondération des attributs produits
//     consultés (catégorie, tranche de prix, tag, rating)
//  2. Vecteur TF-IDF simplifié : chaque produit est représenté
//     par un vecteur de features normalisé
//  3. Similarité cosinus entre le profil utilisateur et chaque
//     produit non encore consulté
//  4. Fallback popularité si pas d'historique
// ============================================================
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

/**
 * Construit le vecteur de features d'un produit.
 * Retourne un tableau associatif feature => valeur (float).
 *
 * Features utilisées :
 *  - cat_{catégorie}       : 1.0 si le produit appartient à cette catégorie
 *  - price_low/mid/high    : tranche de prix (0-300 / 300-1000 / >1000)
 *  - tag_{tag}             : 1.0 si le produit a ce tag
 *  - rating_norm           : note normalisée entre 0 et 1
 */
function buildProductVector(array $p): array {
    $vec = [];

    // ── Feature : catégorie (one-hot) ──────────────────────
    $cat = strtolower(trim($p['category'] ?? ''));
    if ($cat) $vec["cat_$cat"] = 1.0;

    // ── Feature : tranche de prix ──────────────────────────
    $price = (float)($p['price'] ?? 0);
    if      ($price <= 300)  $vec['price_low']  = 1.0;
    elseif  ($price <= 1000) $vec['price_mid']  = 1.0;
    else                     $vec['price_high'] = 1.0;

    // ── Feature : tag (one-hot) ────────────────────────────
    $tag = strtolower(trim($p['tag'] ?? ''));
    if ($tag) $vec["tag_$tag"] = 1.0;

    // ── Feature : note normalisée (0-5 → 0-1) ─────────────
    $vec['rating_norm'] = min(1.0, max(0.0, (float)($p['rating'] ?? 0) / 5.0));

    // ── Feature : popularité normalisée (log scale) ────────
    $reviews = max(1, (int)($p['reviews'] ?? 1));
    $vec['popularity'] = min(1.0, log($reviews + 1) / log(500));

    return $vec;
}

/**
 * Calcule la norme L2 d'un vecteur.
 */
function vecNorm(array $vec): float {
    $sum = 0.0;
    foreach ($vec as $v) $sum += $v * $v;
    return sqrt($sum);
}

/**
 * Similarité cosinus entre deux vecteurs associatifs.
 */
function cosineSimilarity(array $a, array $b): float {
    $dot   = 0.0;
    $normA = vecNorm($a);
    $normB = vecNorm($b);
    if ($normA == 0 || $normB == 0) return 0.0;

    foreach ($a as $feature => $val) {
        if (isset($b[$feature])) {
            $dot += $val * $b[$feature];
        }
    }
    return $dot / ($normA * $normB);
}

/**
 * Construit le profil de goût de l'utilisateur.
 *
 * Le profil est la somme pondérée des vecteurs des produits
 * consultés, multipliée par le score d'interaction.
 * Plus un produit est consulté souvent, plus son influence
 * sur le profil est forte.
 */
function buildUserProfile(int $uid): array {
    $db = db();
    $stmt = $db->prepare("
        SELECT i.product_id, i.score, p.*
        FROM interactions i
        JOIN products p ON p.id = i.product_id
        WHERE i.user_id = ?
        ORDER BY i.score DESC
    ");
    $stmt->execute([$uid]);
    $interactions = $stmt->fetchAll();

    if (empty($interactions)) return [];

    $profile    = [];
    $totalScore = 0.0;

    foreach ($interactions as $inter) {
        $score  = (float)$inter['score'];
        $prodVec = buildProductVector($inter);
        $totalScore += $score;

        foreach ($prodVec as $feature => $val) {
            $profile[$feature] = ($profile[$feature] ?? 0.0) + $val * $score;
        }
    }

    // Normaliser par le score total pour avoir un profil moyen pondéré
    if ($totalScore > 0) {
        foreach ($profile as $f => $v) {
            $profile[$f] = $v / $totalScore;
        }
    }

    return $profile;
}

/**
 * Recommandations basées sur le contenu.
 *
 * Algorithme :
 * 1. Construire le profil utilisateur à partir de ses interactions
 * 2. Pour chaque produit non encore consulté, calculer la
 *    similarité cosinus entre son vecteur et le profil
 * 3. Retourner les $limit produits avec les scores les plus élevés
 * 4. Fallback : popularité si pas d'historique
 */
function getRecommendations(int $uid, int $limit = 6): array {
    $db = db();

    // Profil utilisateur
    $userProfile = buildUserProfile($uid);

    // Fallback si pas d'historique : top popularité
    if (empty($userProfile)) {
        return $db->query(
            "SELECT * FROM products
             ORDER BY (rating * LOG(reviews + 1)) DESC
             LIMIT $limit"
        )->fetchAll();
    }

    // Produits déjà consultés (à exclure des recommandations)
    $stmt = $db->prepare("SELECT product_id FROM interactions WHERE user_id = ?");
    $stmt->execute([$uid]);
    $seen = array_column($stmt->fetchAll(), 'product_id');
    $seenSet = array_flip($seen);

    // Tous les produits disponibles
    $allProducts = $db->query("SELECT * FROM products")->fetchAll();

    // Calculer le score de similarité pour chaque produit non vu
    $scores = [];
    foreach ($allProducts as $p) {
        if (isset($seenSet[$p['id']])) continue; // déjà vu

        $prodVec = buildProductVector($p);
        $sim     = cosineSimilarity($userProfile, $prodVec);

        // Bonus léger pour les produits bien notés (diversité)
        $ratingBonus = (float)$p['rating'] / 5.0 * 0.1;

        $scores[$p['id']] = $sim + $ratingBonus;
    }

    // Trier par score décroissant
    arsort($scores);
    $topIds = array_slice(array_keys($scores), 0, $limit);

    if (empty($topIds)) {
        // Tous les produits ont été vus → recommander les mieux notés
        return $db->query(
            "SELECT * FROM products
             ORDER BY (rating * LOG(reviews + 1)) DESC
             LIMIT $limit"
        )->fetchAll();
    }

    // Récupérer les produits dans l'ordre de score
    $in = implode(',', array_map('intval', $topIds));
    return $db->query(
        "SELECT * FROM products
         WHERE id IN($in)
         ORDER BY FIELD(id, $in)"
    )->fetchAll();
}

/**
 * Enregistre une interaction utilisateur-produit.
 * Le score monte progressivement jusqu'à 5 (max).
 */
function trackInteraction(int $uid, int $pid, int $score = 1): void {
    db()->prepare("
        INSERT INTO interactions (user_id, product_id, score)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            score      = LEAST(score + 1, 5),
            updated_at = NOW()
    ")->execute([$uid, $pid, $score]);
}

/**
 * Explique pourquoi un produit est recommandé (debug/admin).
 * Retourne les features dominantes du match.
 */
function explainRecommendation(int $uid, int $pid): array {
    $db = db();
    $userProfile = buildUserProfile($uid);
    if (empty($userProfile)) return [];

    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$pid]); $p = $stmt->fetch();
    if (!$p) return [];

    $prodVec = buildProductVector($p);
    $matches = [];

    foreach ($prodVec as $feature => $val) {
        if (isset($userProfile[$feature]) && $userProfile[$feature] > 0) {
            $contribution = $val * $userProfile[$feature];
            if ($contribution > 0.01) {
                $matches[$feature] = round($contribution, 3);
            }
        }
    }
    arsort($matches);
    return $matches;
}
