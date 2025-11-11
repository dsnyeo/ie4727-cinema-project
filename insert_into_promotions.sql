INSERT INTO promotions (PromoID, PromoName, PromoImage, PromoDescription, PromoCode) VALUES
(1, 'Returning Customer Discount', './images/promotions/promo1.jpg', 'Returning Customers get to enjoy 20% off...', 'SECOND1')

ON DUPLICATE KEY UPDATE
  PromoName = VALUES(PromoName),
  PromoImage = VALUES(PromoImage),
  PromoDescription = VALUES(PromoDescription),
  PromoCode = VALUES(PromoCode);
