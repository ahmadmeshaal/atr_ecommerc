<?php

final class GuestWishlist
{
    private const SESSION_KEY = 'guest_wishlist';
    private const MAX_ITEMS = 100;

    public static function ids(): array
    {
        $stored = $_SESSION[self::SESSION_KEY] ?? [];
        if (!is_array($stored)) {
            return [];
        }

        $ids = [];
        foreach ($stored as $value) {
            $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id !== false) {
                $ids[(int) $id] = (int) $id;
            }
        }

        return array_values($ids);
    }

    public static function getActiveProducts(PDO $pdo): array
    {
        $ids = self::ids();
        if ($ids === []) {
            return [];
        }

        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id AS product_id FROM products WHERE is_active = 1 AND id IN ($marks)");
        $stmt->execute($ids);
        $active = array_fill_keys(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)), true);

        return array_map(
            static fn(int $id): array => ['product_id' => $id],
            array_values(array_filter($ids, static fn(int $id): bool => isset($active[$id])))
        );
    }

    public static function add(int $productId): bool
    {
        $ids = self::ids();
        if (in_array($productId, $ids, true)) {
            return false;
        }
        if (count($ids) >= self::MAX_ITEMS) {
            throw new LengthException('Your guest wishlist can contain up to 100 fragrances.');
        }

        $ids[] = $productId;
        $_SESSION[self::SESSION_KEY] = $ids;
        return true;
    }

    public static function remove(int $productId): bool
    {
        $ids = self::ids();
        if (!in_array($productId, $ids, true)) {
            return false;
        }

        $_SESSION[self::SESSION_KEY] = array_values(array_filter(
            $ids,
            static fn(int $id): bool => $id !== $productId
        ));
        return true;
    }

    public static function mergeInto(PDO $pdo, int $userId): void
    {
        $ids = self::ids();
        if ($ids === []) {
            return;
        }

        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id FROM products WHERE is_active = 1 AND id IN ($marks)");
        $stmt->execute($ids);
        $activeIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $insert = $pdo->prepare('INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)');
            foreach ($activeIds as $productId) {
                $insert->execute([$userId, $productId]);
            }
            if ($ownsTransaction) {
                $pdo->commit();
            }
            unset($_SESSION[self::SESSION_KEY]);
        } catch (Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}