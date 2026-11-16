-- Align the local legacy reviews table with the review API and add moderation state.
-- Existing rows, if any, stay visible as approved reviews.
ALTER TABLE `reviews`
  ADD COLUMN `order_id` int(10) unsigned NOT NULL AFTER `product_id`,
  ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `comment`,
  ADD COLUMN `moderation_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  ADD COLUMN `moderation_category` varchar(50) DEFAULT NULL,
  ADD COLUMN `moderation_reason` text DEFAULT NULL,
  ADD COLUMN `moderated_at` datetime DEFAULT NULL,
  ADD KEY `fk_reviews_order_id` (`order_id`),
  ADD CONSTRAINT `fk_reviews_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);
