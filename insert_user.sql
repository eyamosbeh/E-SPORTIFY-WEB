-- Insérer un utilisateur avec mot de passe haché (mot de passe: password123)
INSERT INTO `user` (`email`, `roles`, `password`, `nom`, `prenom`) 
VALUES 
('admin@example.com', '["ROLE_ADMIN"]', '$2y$13$nFY3beUu1wA8q2uNHu3OL.Hmi/3DlTrwxxrqsUPJnYD1YZVZM2h7i', 'Admin', 'User'),
('joueur@example.com', '["ROLE_USER"]', '$2y$13$nFY3beUu1wA8q2uNHu3OL.Hmi/3DlTrwxxrqsUPJnYD1YZVZM2h7i', 'Joueur', 'Simple');

-- Le mot de passe pour les deux utilisateurs est: password123 