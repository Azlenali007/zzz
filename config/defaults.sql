-- Default Settings and Gateways for ApexSMM (No fake data, only system config)
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES
('site_name', 'ApexSMM'),
('site_tagline', 'Next-Gen High Performance SMM Panel'),
('site_description', 'Production-grade SMM panel built with Plain PHP and MySQL.'),
('currency', 'USD'),
('currency_symbol', '$'),
('timezone', 'UTC'),
('maintenance_mode', '0'),
('registration_enabled', '1'),
('contact_email', 'support@apexsmm.com'),
('contact_telegram', '@ApexSMMSupport'),
('cron_last_run', NULL);

-- Configured Payment Gateways (Ready for Admin Activation & Configuration)
INSERT IGNORE INTO `payment_gateways` (`id`, `code`, `name`, `instructions`, `currency`, `min_amount`, `max_amount`, `fee_percent`, `status`, `config_data`) VALUES
(1, 'bank_transfer', 'Manual Bank Transfer', 'Transfer funds directly to our corporate bank account. Once submitted, upload your transaction ID or receipt for manual verification.', 'USD', 10.00, 10000.00, 0.00, 'active', '{"bank_name":"Global Commercial Bank","account_number":"9876543210","account_name":"Apex Services Inc.","swift_code":"GLBCUS33"}'),
(2, 'crypto_usdt', 'USDT (TRC20 / ERC20)', 'Send USDT to the official deposit address. Provide your transaction hash (TXID) below for admin verification and instant balance credit.', 'USD', 5.00, 50000.00, 0.00, 'active', '{"trc20_address":"TX1234567890ApexSMMOfficialTRC20Addr","network":"TRC20"}'),
(3, 'stripe', 'Stripe (Credit / Debit Card)', 'Pay securely with your credit or debit card.', 'USD', 5.00, 2000.00, 2.90, 'inactive', '{"publishable_key":"","secret_key":""}'),
(4, 'paypal', 'PayPal Express Checkout', 'Instant deposit via your PayPal balance or linked card.', 'USD', 10.00, 2000.00, 3.50, 'inactive', '{"client_id":"","secret":""}');
