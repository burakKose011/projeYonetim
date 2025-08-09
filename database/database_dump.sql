-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: proje_yonetim
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Temporary table structure for view `all_projects_admin`
--

DROP TABLE IF EXISTS `all_projects_admin`;
/*!50001 DROP VIEW IF EXISTS `all_projects_admin`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `all_projects_admin` AS SELECT
 1 AS `id`,
  1 AS `title`,
  1 AS `subject`,
  1 AS `description`,
  1 AS `keywords`,
  1 AS `project_manager`,
  1 AS `responsible_person`,
  1 AS `start_date`,
  1 AS `end_date`,
  1 AS `duration_days`,
  1 AS `status`,
  1 AS `budget`,
  1 AS `funding_source`,
  1 AS `created_by`,
  1 AS `approved_by`,
  1 AS `approved_at`,
  1 AS `rejection_reason`,
  1 AS `created_at`,
  1 AS `updated_at`,
  1 AS `creator_name`,
  1 AS `creator_username`,
  1 AS `creator_department`,
  1 AS `creator_title` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` enum('info','success','warning','error','approval') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `related_project_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `related_project_id` (`related_project_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`related_project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'Yeni Proje Eklendi','Yeni proje eklendi: proje yönetim sistemi','info',0,3,'2025-07-19 15:11:55'),(2,1,'Yeni Proje Eklendi','Yeni proje eklendi: Hayvan Sahiplendirme','info',0,NULL,'2025-07-19 15:42:36'),(3,1,'Yeni Proje Eklendi','Yeni proje eklendi: Hayvan Sahiplendirme','info',0,5,'2025-07-19 15:43:30'),(4,1,'Yeni Proje Eklendi','Yeni proje eklendi: mimarlık','info',0,6,'2025-07-19 15:47:51'),(5,1,'Yeni Proje Eklendi','Yeni proje eklendi: araba kiralama','info',0,7,'2025-07-19 15:53:02'),(6,49,'Proje Reddedildi','Projeniz reddedildi. Sebep: yetersiz proje','error',0,7,'2025-07-19 16:00:00'),(7,1,'Yeni Proje Eklendi','Yeni proje eklendi: Çevreyi Koruma','info',0,8,'2025-07-21 10:04:11'),(8,49,'Proje Onaylandı','Projeniz onaylandı.','success',0,5,'2025-07-21 10:18:35'),(9,584,'Proje Reddedildi','Projeniz reddedildi. Sebep: yetersiz proje','error',0,8,'2025-08-04 08:35:58'),(10,2,'Proje Onaylandı','Projeniz onaylandı.','success',0,3,'2025-08-04 08:55:46'),(11,49,'Proje Reddedildi','Projeniz reddedildi. Sebep: yetersiz tasarım ve proje','error',0,6,'2025-08-07 11:15:37'),(12,1,'Yeni Proje Eklendi','Yeni proje eklendi: yenilenebilir enerjili araç','info',0,11,'2025-08-07 11:40:06');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_comments`
--

DROP TABLE IF EXISTS `project_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `comment_type` enum('general','feedback','approval','rejection') DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `project_comments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_comments`
--

LOCK TABLES `project_comments` WRITE;
/*!40000 ALTER TABLE `project_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_files`
--

DROP TABLE IF EXISTS `project_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  CONSTRAINT `project_files_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_files_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_files`
--

LOCK TABLES `project_files` WRITE;
/*!40000 ALTER TABLE `project_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_requirements`
--

DROP TABLE IF EXISTS `project_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `requirement_type` enum('equipment','material','software','other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('pending','approved','ordered','received') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `project_requirements_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_requirements`
--

LOCK TABLES `project_requirements` WRITE;
/*!40000 ALTER TABLE `project_requirements` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `project_manager` varchar(100) DEFAULT NULL,
  `responsible_person` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `duration_days` int(11) DEFAULT NULL,
  `file_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`file_info`)),
  `status` enum('new','pending','approved','rejected','in_progress','completed') DEFAULT 'new',
  `budget` decimal(15,2) DEFAULT 0.00,
  `funding_source` varchar(100) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_status` (`status`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_end_date` (`end_date`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (3,'proje yönetim sistemi','sistemsel proje yönetimi','bu projede üniversiteler projeleri sistemden takip edebilecek','proje','burak','yusuf efe','2025-07-20','2025-07-30',10,'{\"name\":\"villam-3.png\",\"type\":\"image\\/png\",\"size\":\"6.7 MB\",\"uploadDate\":\"2025-07-28T08:14:44.573Z\"}','approved',0.00,NULL,2,1,'2025-08-04 08:55:46',NULL,'2025-07-19 15:11:55','2025-08-04 08:55:46'),(5,'Hayvan Sahiplendirme','hayvanları ev bulma','hayvanları sahiplendirmek için bir site yapımı','hayvan','ayaz','Barış','2025-07-22','2025-07-27',5,'{\"name\":\"green1.png\",\"size\":\"460.3 KB\",\"type\":\"PNG Resmi\",\"uploadDate\":\"2025-07-19T15:43:29.987Z\"}','approved',0.00,NULL,49,1,'2025-07-21 10:18:35',NULL,'2025-07-19 15:43:30','2025-07-21 10:18:35'),(6,'mimarlık','mimari site','mimarlık için hem portfolyo hem tasarımlarını tanıtabileceği bir site','yapı','mutlu','Burak','2025-07-15','2025-07-31',16,'{\"name\":\"arb.jpeg\",\"size\":\"142.59 KB\",\"type\":\"JPEG Resmi\",\"uploadDate\":\"2025-07-28 08:37:10\"}','rejected',0.00,NULL,49,1,'2025-08-07 11:15:37','yetersiz tasarım ve proje','2025-07-19 15:47:51','2025-08-07 11:35:03'),(7,'araba kiralama','araç kiralama','araçların satış işlemlerini ve diğer işlemlerini gerçekleştirebileceği bir proje','araba','ahmet gökçen','mehmet Ayyıldız','2025-07-09','2025-07-29',20,'{\"name\":\"green2.png\",\"size\":\"147.62 KB\",\"type\":\"PNG Resmi\",\"uploadDate\":\"2025-07-19T15:53:02.217Z\"}','rejected',0.00,NULL,49,1,'2025-07-19 16:00:00','yetersiz proje','2025-07-19 15:53:02','2025-08-07 11:35:03'),(8,'Çevreyi Koruma','Doğayı yeşillendirmek','Doğayı korumak ve çevremizi yeşillendirmek için oluşturulmuş proje','doğa koruma','Kerem','Fatih','2025-08-19','2025-08-30',11,'{\"name\":\"pexels-eberhardgross-691668.jpg\",\"size\":\"1.82 MB\",\"type\":\"JPEG Resmi\",\"uploadDate\":\"2025-07-21T10:04:10.903Z\"}','rejected',0.00,NULL,584,1,'2025-08-04 08:35:58','yetersiz proje','2025-07-21 10:04:11','2025-08-04 08:35:58'),(11,'yenilenebilir enerjili araç','Elektrikli araç','doğal enerji ile yenilenebilir araç tasarlama ve uygulama','enerji','Ferhat','Emir','2025-08-28','2025-09-18',20,'{\"name\":\"Ekran Resmi 2024-11-09 20.08.15.png\",\"size\":\"1.5 MB\",\"type\":\"PNG Resmi\",\"uploadDate\":\"2025-08-07T11:40:06.552Z\"}','pending',0.00,NULL,2,NULL,NULL,NULL,'2025-08-07 11:40:06','2025-08-07 11:40:06');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'site_name','İSTÜ Proje Yönetim Sistemi','Site başlığı','2025-07-17 20:58:33'),(2,'admin_email','admin@istu.edu.tr','Yönetici e-posta adresi','2025-07-17 20:58:33'),(3,'max_file_size','10485760','Maksimum dosya boyutu (10MB)','2025-07-17 20:58:33'),(4,'allowed_file_types','pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar,txt','İzin verilen dosya türleri','2025-07-17 20:58:33'),(5,'project_auto_approval','false','Projelerin otomatik onaylanması','2025-07-17 20:58:33'),(6,'notification_email','true','E-posta bildirimleri aktif','2025-07-17 20:58:33');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `user_projects`
--

DROP TABLE IF EXISTS `user_projects`;
/*!50001 DROP VIEW IF EXISTS `user_projects`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `user_projects` AS SELECT
 1 AS `id`,
  1 AS `title`,
  1 AS `subject`,
  1 AS `description`,
  1 AS `keywords`,
  1 AS `project_manager`,
  1 AS `responsible_person`,
  1 AS `start_date`,
  1 AS `end_date`,
  1 AS `duration_days`,
  1 AS `status`,
  1 AS `budget`,
  1 AS `funding_source`,
  1 AS `created_by`,
  1 AS `approved_by`,
  1 AS `approved_at`,
  1 AS `rejection_reason`,
  1 AS `created_at`,
  1 AS `updated_at`,
  1 AS `creator_name`,
  1 AS `creator_username` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `title` varchar(50) DEFAULT 'Dr. Öğr. Üyesi',
  `department` varchar(100) DEFAULT 'Bilgisayar Mühendisliği',
  `employee_id` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=1884 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@iste.edu.tr','$2y$12$DoKiZPEqxqPMUJ/IcinrYuioklL0LRSpAZum4rdmQqWNmqSx0T6xu','Sistem Yöneticisi','Prof. Dr.','Bilgi İşlem','ADMIN001','05551234567','admin',1,'2025-08-07 11:46:01','2025-07-17 20:58:33','2025-08-07 11:46:01'),(2,'demo','demo@iste.edu.tr','$2y$10$hq20cp5i9JyscPnSfFD2F.7qQ4EQqiNsu2k/mZiwyiCoV77s9SUBu','Demo Kullanıcı','Prof. Dr.','Bilgisayar Mühendisliği','2024001','05551234568','user',1,'2025-08-07 11:36:52','2025-07-17 20:58:33','2025-08-07 11:36:52'),(49,'testuserTest Kullanıcı','test@iste.edu.tr','$2y$10$kZzDGQRrSenC4Y9IzYi2OuwsxCmGNBFXjQeGAhetLMKqBTCwP6yV.','Test Kullanıcı','Dr. Öğr. Üyesi','Bilgisayar Mühendisliği','2024002','05551234569','user',1,'2025-08-07 11:49:43','2025-07-19 14:43:14','2025-08-07 11:49:43'),(584,'mert','mert@iste.edu.tr','$2y$12$M/tMRi0P54x6kbe5FijPWucsyZORIs0SVtya7IBf0avo8IWvT.Skm','Mert Yılmaz','Öğr. Gör.','Eczacılık','1111111','0544444444','user',1,'2025-07-30 15:00:52','2025-07-21 10:00:20','2025-07-30 15:00:52'),(1539,'burak','burak@iste.edu.tr','$2y$10$cpWMlmRCAU51i.6DLPIoDOH/Hl//3odrOJLk5bk4dzyKmwBjIKmm2','Burak Köse','Öğrenci','Bilgisayar Mühendisliği ','111111','05111111111','user',1,'2025-08-07 11:21:17','2025-08-04 10:20:28','2025-08-07 11:21:17'),(1642,'testuser','testuser@iste.edu.tr','$2y$12$DoKiZPEqxqPMUJ/IcinrYuioklL0LRSpAZum4rdmQqWNmqSx0T6xu','Test Kullanıcı','Öğr. Gör.','Bilgisayar Mühendisliği',NULL,NULL,'user',1,'2025-08-07 11:18:25','2025-08-07 11:18:12','2025-08-07 11:18:25');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `all_projects_admin`
--

/*!50001 DROP VIEW IF EXISTS `all_projects_admin`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `all_projects_admin` AS select `p`.`id` AS `id`,`p`.`title` AS `title`,`p`.`subject` AS `subject`,`p`.`description` AS `description`,`p`.`keywords` AS `keywords`,`p`.`project_manager` AS `project_manager`,`p`.`responsible_person` AS `responsible_person`,`p`.`start_date` AS `start_date`,`p`.`end_date` AS `end_date`,`p`.`duration_days` AS `duration_days`,`p`.`status` AS `status`,`p`.`budget` AS `budget`,`p`.`funding_source` AS `funding_source`,`p`.`created_by` AS `created_by`,`p`.`approved_by` AS `approved_by`,`p`.`approved_at` AS `approved_at`,`p`.`rejection_reason` AS `rejection_reason`,`p`.`created_at` AS `created_at`,`p`.`updated_at` AS `updated_at`,`u`.`full_name` AS `creator_name`,`u`.`username` AS `creator_username`,`u`.`department` AS `creator_department`,`u`.`title` AS `creator_title` from (`projects` `p` join `users` `u` on(`p`.`created_by` = `u`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `user_projects`
--

/*!50001 DROP VIEW IF EXISTS `user_projects`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `user_projects` AS select `p`.`id` AS `id`,`p`.`title` AS `title`,`p`.`subject` AS `subject`,`p`.`description` AS `description`,`p`.`keywords` AS `keywords`,`p`.`project_manager` AS `project_manager`,`p`.`responsible_person` AS `responsible_person`,`p`.`start_date` AS `start_date`,`p`.`end_date` AS `end_date`,`p`.`duration_days` AS `duration_days`,`p`.`status` AS `status`,`p`.`budget` AS `budget`,`p`.`funding_source` AS `funding_source`,`p`.`created_by` AS `created_by`,`p`.`approved_by` AS `approved_by`,`p`.`approved_at` AS `approved_at`,`p`.`rejection_reason` AS `rejection_reason`,`p`.`created_at` AS `created_at`,`p`.`updated_at` AS `updated_at`,`u`.`full_name` AS `creator_name`,`u`.`username` AS `creator_username` from (`projects` `p` join `users` `u` on(`p`.`created_by` = `u`.`id`)) where `u`.`role` = 'user' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-07 16:14:09
