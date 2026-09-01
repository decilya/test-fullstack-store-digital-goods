-- Тестовые данные

INSERT OR IGNORE INTO products (sku, name, type, price, image) VALUES
('STEAM-TOPUP-500', 'Пополнение Steam 500 ₽', 'topup', 500, 'assets/steam.png'),
('STEAM-TOPUP-1000', 'Пополнение Steam 1000 ₽', 'topup', 1000, 'assets/steam.png'),
('STEAM-TOPUP-2500', 'Пополнение Steam 2500 ₽', 'topup', 2500, 'assets/steam.png'),
('KEY-CS2-PRIME', 'CS2 Prime Status ключ', 'key', 1290, 'assets/cs2.png'),
('KEY-GTA5', 'GTA V ключ активации', 'key', 1990, 'assets/gta5.png'),
('KEY-EFT', 'Escape from Tarkov ключ', 'key', 3490, 'assets/eft.png'),
('SUB-DISCORD-1M', 'Discord Nitro 1 месяц', 'subscription', 399, 'assets/discord.png'),
('SUB-YT-3M', 'YouTube Premium 3 месяца', 'subscription', 1490, 'assets/youtube.png'),
('SUB-SPOTIFY-1M', 'Spotify Premium 1 месяц', 'subscription', 299, 'assets/spotify.png'),
('GIFT-PSN-1000', 'PlayStation Store карта 1000 ₽', 'giftcard', 1000, 'assets/psn.png'),
('GIFT-XBOX-1500', 'Xbox Gift Card 1500 ₽', 'giftcard', 1500, 'assets/xbox.png'),
('GIFT-ROBLOX-800', 'Roblox 800 Robux', 'giftcard', 890, 'assets/roblox.png');

-- Пул тестовых ключей (50 штук)
INSERT OR IGNORE INTO keys_pool (sku, code) VALUES
('KEY-CS2-PRIME', 'LFXC-TNCS-BPCD'),
('KEY-CS2-PRIME', 'P3EI-W8UO-9B4K'),
('KEY-CS2-PRIME', 'FEL3-GUXN-TCCH'),
('KEY-CS2-PRIME', 'YPLV-QK2Z-IUS5'),
('KEY-CS2-PRIME', '0K9E-P1FR-BY1U'),
('KEY-CS2-PRIME', '5LZV-UQ48-RXCZ'),
('KEY-CS2-PRIME', 'X93K-NYAQ-GEC1'),
('KEY-CS2-PRIME', 'EIO5-CQT5-35KO'),
('KEY-CS2-PRIME', 'M58F-GIIR-VJAP'),
('KEY-CS2-PRIME', 'NU8Y-SWYB-6252'),
('KEY-GTA5', 'OODW-CCHF-MBAF'),
('KEY-GTA5', 'DNA5-WFJM-NE49'),
('KEY-GTA5', 'QRDD-MJ3F-A8TF'),
('KEY-GTA5', 'TAT9-5ZJN-G1T2'),
('KEY-GTA5', 'LI39-4330-ISMB'),
('KEY-GTA5', 'BKJY-8Q79-8NHI'),
('KEY-GTA5', 'HHW6-4RX2-DX62'),
('KEY-GTA5', '1RG2-L28O-O80G'),
('KEY-GTA5', 'EF63-F39X-MTEA'),
('KEY-GTA5', '8XS7-P53H-JKIV'),
('KEY-EFT', 'JPE6-MQV6-P7ST'),
('KEY-EFT', 'SAPG-A2GR-0ULS'),
('KEY-EFT', 'T2DU-IJ1S-U16P'),
('KEY-EFT', 'WSSY-QTR7-Z57J'),
('KEY-EFT', 'U74E-EPCI-CY26'),
('KEY-EFT', 'FZXF-58H8-OR93'),
('KEY-EFT', 'FPSM-HLZA-TPAL'),
('KEY-EFT', 'WSC9-28DJ-B2JE'),
('KEY-EFT', 'P63J-F7UZ-DCYP'),
('KEY-EFT', 'C7W2-D4C5-QMT7');

-- Промокоды
INSERT OR IGNORE INTO promo_codes (code, type, value, max_uses) VALUES
('WELCOME10', 'percent', 10, 100),
('GG500', 'amount', 500, 20),
('LIMIT3', 'percent', 25, 3),
('ONCEONLY', 'percent', 50, 1);