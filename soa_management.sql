-- MySQL dump 10.13  Distrib 8.0.29, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: soa_management
-- ------------------------------------------------------
-- Server version	8.0.29

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `claim_meal_entries`
--

DROP TABLE IF EXISTS `claim_meal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `claim_meal_entries` (
  `meal_id` int NOT NULL AUTO_INCREMENT,
  `claim_id` int NOT NULL,
  `meal_date` date NOT NULL,
  `meal_type` enum('Breakfast','Lunch','Dinner','Other') COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `receipt_reference` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`meal_id`),
  KEY `claim_id` (`claim_id`),
  CONSTRAINT `claim_meal_entries_ibfk_1` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`claim_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `claim_meal_entries`
--

LOCK TABLES `claim_meal_entries` WRITE;
/*!40000 ALTER TABLE `claim_meal_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `claim_meal_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `claim_travel_entries`
--

DROP TABLE IF EXISTS `claim_travel_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `claim_travel_entries` (
  `entry_id` int NOT NULL AUTO_INCREMENT,
  `claim_id` int NOT NULL,
  `travel_date` date NOT NULL,
  `travel_from` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `travel_to` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `parking_fee` decimal(10,2) DEFAULT '0.00',
  `toll_fee` decimal(10,2) DEFAULT '0.00',
  `miles_traveled` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_id`),
  KEY `claim_id` (`claim_id`),
  CONSTRAINT `claim_travel_entries_ibfk_1` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`claim_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `claim_travel_entries`
--

LOCK TABLES `claim_travel_entries` WRITE;
/*!40000 ALTER TABLE `claim_travel_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `claim_travel_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `claims`
--

DROP TABLE IF EXISTS `claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `claims` (
  `claim_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `claim_month` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vehicle_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `travel_date` date DEFAULT NULL,
  `travel_from` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `travel_to` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parking_fee` decimal(10,2) DEFAULT NULL,
  `toll_fee` decimal(10,2) DEFAULT NULL,
  `miles_traveled` decimal(10,2) DEFAULT NULL,
  `km_rate` decimal(10,2) DEFAULT NULL,
  `total_km_amount` decimal(10,2) DEFAULT NULL,
  `total_meal_amount` decimal(10,2) DEFAULT '0.00',
  `amount` decimal(10,2) NOT NULL,
  `employee_signature` tinyint(1) DEFAULT '0',
  `signature_date` date DEFAULT NULL,
  `approval_signature` tinyint(1) DEFAULT '0',
  `approval_date` date DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `submitted_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_date` timestamp NULL DEFAULT NULL,
  `processed_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `approved_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_general_ci,
  `payment_details` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`claim_id`),
  KEY `staff_id` (`staff_id`),
  KEY `processed_by` (`processed_by`),
  CONSTRAINT `claims_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`),
  CONSTRAINT `claims_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `claims`
--

LOCK TABLES `claims` WRITE;
/*!40000 ALTER TABLE `claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_soa`
--

DROP TABLE IF EXISTS `client_soa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_soa` (
  `soa_id` int NOT NULL AUTO_INCREMENT,
  `account_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `client_id` int NOT NULL,
  `terms` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `purchase_date` date DEFAULT NULL,
  `issue_date` date NOT NULL,
  `po_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `invoice_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `due_date` date NOT NULL,
  `service_description` text COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('Pending','Paid','Overdue','Closed') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`soa_id`),
  KEY `client_id` (`client_id`),
  KEY `created_by` (`created_by`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `client_soa_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  CONSTRAINT `client_soa_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`),
  CONSTRAINT `client_soa_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `experience_categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_soa`
--

LOCK TABLES `client_soa` WRITE;
/*!40000 ALTER TABLE `client_soa` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_soa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_statements`
--

DROP TABLE IF EXISTS `client_statements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_statements` (
  `statement_id` int NOT NULL AUTO_INCREMENT,
  `statement_number` varchar(50) NOT NULL,
  `client_id` int NOT NULL,
  `statement_date` date NOT NULL,
  `period_from` date NOT NULL,
  `period_to` date NOT NULL,
  `total_invoiced` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `balance_due` decimal(10,2) NOT NULL DEFAULT '0.00',
  `notes` text,
  `generated_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`statement_id`),
  UNIQUE KEY `statement_number` (`statement_number`),
  KEY `client_id` (`client_id`),
  KEY `generated_by` (`generated_by`),
  CONSTRAINT `client_statements_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE,
  CONSTRAINT `client_statements_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_statements`
--

LOCK TABLES `client_statements` WRITE;
/*!40000 ALTER TABLE `client_statements` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_statements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `client_id` int NOT NULL AUTO_INCREMENT,
  `client_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `address` text COLLATE utf8mb4_general_ci NOT NULL,
  `pic_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `pic_contact` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `pic_email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES (9,'Dewan Bandaraya Kuala Lumpur','Menara DBKL 1, Jalan Raja Laut','Wan Izuddin Bin Wan Idris','60320282047','wanizuddin@dbkl.gov.my','2026-02-24 03:30:19','2026-02-24 03:30:19'),(17,'Handal Cranes Sdn Bhd','Lot PT 7358, Kawasan Perindustrian Telok Kalong','Siti Erna','6098602042','siti.erna@handalenergy.com','2026-02-24 04:01:03','2026-02-24 04:01:03'),(18,'Majlis Daerah Rompin','Pejabat MD Rompin, Kuala Rompin, Pahang, \r\n26800','Mohamad Shaifulnizam Bin Gaya','60143377943','shaifulnizamgaya@mdrompin.gov.my','2026-02-24 04:03:37','2026-02-24 04:03:37'),(19,'Majlis Daerah Sabak Bernam','Majlis Daerah Sabak Bernam\r\nSungai Besar, Selangor\r\n45300','Muhammad Alif Amalulhair','60332241655','alif@mdsb.gov.my','2026-02-24 04:05:39','2026-02-24 04:05:39'),(20,'Majlis Daerah Segamat','No.1, Jalan Abdullah\r\n85000, Segamat, Johor','Mohd Hazwan Akmal Bin Mohamad','6079314455','hazwanakmal@johor.gov.my','2026-02-24 04:06:48','2026-02-24 04:06:48'),(21,'Handal Offshore Services Sdn Bhd','C-L29-08, KL Trillion 338, Jalan Tun Razak','Mohamad Azizul Bin Mohd Noor','6098632842','azizul.noor@handalenergy.my','2026-02-24 04:06:57','2026-02-24 04:06:57'),(22,'Majlis Daerah Selama','Selama, Perak. 34100','Nabila Huda Binti Nasrudin','6058394201','nabilahuda@mdselama.gov.my','2026-02-24 04:08:19','2026-02-24 04:08:19'),(23,'Majlis Daerah Setiu','Wisma MDS, Bandar Permaisuri	Setiu	Terengganu	22100','Naziman	Bin Muhamat','6096099377','naziman@mds.gov.my','2026-02-24 04:09:27','2026-02-24 04:09:27'),(24,'Majlis Daerah Sik','Majlis Daerah Sik, Sik, Kedah','Mohamad Zahir	Bin Md Ideris','6044676031','zahir@mdsik.gov.my','2026-02-24 04:10:20','2026-02-24 04:10:20'),(25,'Majlis Daerah Simpang Renggam','Pejabat MD Simpang Renggam,	Simpang Renggam,	Johor, 86200','Nurhidayah	Binti Rubu @ Nordin','6077551300','nurhidayah@mdsrenggam.gov.my','2026-02-24 04:12:15','2026-02-24 04:12:15'),(27,'Majlis Daerah Tampin','Majlis Daerah Tampin	Tampin	Negeri Sembilan	73000','Mohd Firdaus	Bin Haron','6064414362','firdaus@mdtampin.gov.my','2026-02-24 04:13:07','2026-02-24 04:13:07'),(28,'HR 2000 Sdn Bhd','No 9A, Jalan USJ 10/1C','HR 2000 Sdn Bhd','60356329094','sales@hr2000.com.my','2026-02-24 04:14:04','2026-02-24 04:14:04'),(29,'Majlis Daerah Tanah Merah','Majlis Daerah Tanah Merah, Tanah Merah,	Kelantan,	17500','Mohd Khairul Izwan Bin Che Soh','6099556023','khairulizwan@kelantan.gov.my','2026-02-24 04:14:22','2026-02-24 04:14:22'),(30,'ILS Energy (M) Sdn Bhd','Empty field','ILS Energy (M) Sdn Bhd','222222222','fadhli@ilsenergy.com.my','2026-02-24 04:15:07','2026-02-24 04:15:07'),(31,'Infinitex Sdn Bhd','D-01-01, Menara Mitraland, No. 13A, jalan PJU 5/1, Kota Damansara','Infinitex Sdn Bhd','60376248073','enquiry@infinitex.com.my','2026-02-24 04:15:47','2026-02-24 04:15:47'),(32,'Majlis Daerah Tanah Merah (MDTM)','Majlis Daerah Tanah Merah (MDTM)','Majlis Daerah Tanah Merah (MDTM)','6099556023','mdtm@kelantan.gov.my','2026-02-24 04:15:57','2026-02-24 04:15:57'),(33,'Majlis Daerah Tangkak','Pejabat Besar Majlis Daerah Tangkak, Peti Surat No 63	Tangkak, Johor, 84907','Assrul Bin Zanury','609781261','assrul@johor.gov.my','2026-02-24 04:17:21','2026-02-24 04:17:21'),(34,'Majlis Daerah Tanjung Malim','Peti Surat 59, Bandar Behrang 2020, Tanjung Malim, Perak, 35900','Shahzan Hafiz	Bin Zainal Abidin','6054563437','hafiz@mdtm.gov.my','2026-02-24 04:18:38','2026-02-24 04:18:38'),(35,'Majlis Daerah Tapah','Jalan Stesyen, Tapah, Perak, 35000','Mohd Iqbal	Bin Mohd Noor','6054011326','iqbal@mdtapah.gov.my','2026-02-24 04:19:47','2026-02-24 04:19:47'),(36,'Majlis Daerah Tumpat','Jalan Tanjong Kuala, Pekan Tumpat,Tumpat, Kelantan, 16200','Azilan Bin Mokhtar','6097252241','azilan@kelantan.gov.my','2026-02-24 04:23:54','2026-02-24 04:23:54'),(37,'Majlis Daerah Yan','Majlis Daerah Yan, Yan, Kedah, 06900','Shah Rizal Bin Mohamad','6044655745','shahrizal@mdy.gov.my','2026-02-24 04:25:06','2026-02-24 04:25:06'),(38,'Majlis Daerah Yong Peng','KM 1, Jalan Labis	Yong Peng, Johor, 83700','Rasidi Bin Mohid','6074671276','rasidi_mohid@mdyongpeng.gov.my','2026-02-24 04:26:15','2026-02-24 04:26:15'),(39,'Majlis Perbandaran Alor Gajah','Jalan Dato\' Dol Said, Alor Gajah, Melaka, 78000','Nor Elyani Binti Ramli','6063333333','norelyani@mpag.gov.my','2026-02-24 04:28:16','2026-02-24 04:28:16'),(41,'Majlis Perbandaran Ampang Jaya','Menara MPAJ Jalan Pandan Utama, Pandan Indah, Wilayah Persekutuan Kuala Lumpur, 43000','Mohd Ariff Bin Jaafar','60342857130','ariff@mpaj.gov.my','2026-02-24 04:29:39','2026-02-24 04:29:39'),(42,'Institut Integriti Malaysia','Institut Integriti Malaysia','Institut Integriti Malaysia','222222222','notavailable@mail.com','2026-02-24 04:29:42','2026-02-24 04:29:42'),(43,'International Islamic Liquidity Management (IILM)','Suite 42B Level 43, Vista Tower, The Intermark, 348 Jalan Tun Razak','Ibrahim Malik','60321705000','amibrahim@iilm.com','2026-02-24 04:30:35','2026-02-24 04:30:35'),(44,'Majlis Perbandaran Batu Pahat','Menara MPBP, Jalan Rugayah	Batu Pahat, Johor, 83000','Saipul Akma Bin Adam','1300886727','saipul@mpbp.gov.my','2026-02-24 04:31:10','2026-02-24 04:31:10'),(45,'Jabatan Perancangan Bandar & Desa Pahang','PLANMalaysia@Pahang, Tingkat 4, Kompleks Tun Razak, Bandar Indera Mahkota','Nazima Binti Zahari','6095721181','nazima@pahang.gov.my','2026-02-24 04:31:43','2026-02-24 04:31:43'),(46,'Jabatan Perhutanan Negeri Pahang','Tingkat 5, Kompleks Tun Razak, Bandar Indera Mahkota','Nurul Hafiza Binti Hamdan','6095732911','fiza19@pahang.gov.my','2026-02-24 04:32:18','2026-02-24 04:32:18'),(47,'Majlis Perbandaran Bentong','Jalan Ketari, Bentong, Pahang, 28700','Norfadlina Binti Ariffin','6092221148','fadlina@mpbentong.gov.my','2026-02-24 04:32:47','2026-02-24 04:32:47'),(48,'Jabatan Perkhidmatan Veterinar Negeri Melaka','empty field','Jabatan Perkhidmatan Veterinar Negeri Melaka','6062325102','khalidzulhuddin@melaka.gov.my','2026-02-24 04:33:05','2026-02-24 04:33:05'),(50,'Majlis Perbandaran Dungun','Jalan Yahaya Ahmad, Dungun	, Terengganu, 23000','Nor Afni Raziah Binti Alias','6098481931','afniraziah@mpd.gov.my','2026-02-24 04:34:04','2026-02-24 04:34:04'),(51,'Majlis Perbandaran Hang Tuah Jaya','SF-01, Aras 2, Kompleks Melaka Mall, Jalan Tun Abdul Razak - Lebuh Ayer Keroh, Hang Tuah Jaya, Ayer Keroh, Melaka, 75450','Handaina	Binti Samat','6062323773','handaina@mphtj.gov.my','2026-02-24 04:35:59','2026-02-24 04:35:59'),(52,'Majlis Perbandaran Hulu Selangor','Jalan Bukit Kerajaan, Kuala Kubu Bharu, Selangor, 44000','Mohamad Ramdan	Bin Ibrahim','60360641331','ramdan@mphs.gov.my','2026-02-24 04:38:35','2026-02-24 04:38:35'),(53,'Majlis Perbandaran Jasin','Vista Alamanda, Jasin, Melaka, 77000','Mohamad Fadzli	Bin Muhamad Saleh','6063333333','fadzli@mpjasin.gov.my','2026-02-24 04:40:13','2026-02-24 04:40:13'),(54,'Kayaku Safety Systems Malaysia Sdn Bhd','Empty field','Yazmin','6067817031','yazmin.mdyazam@kayaku.com.my','2026-02-24 04:41:39','2026-02-24 04:41:39'),(56,'Majlis Perbandaran Jempol','Majlis Perbandaran Jempol	Bandar Seri Jempol, Negeri Sembilan	72120','Amirnizan Bin Abdul Wahab','6064581233','mpjempol@mpjl.gov.my','2026-02-24 04:42:42','2026-02-24 04:42:42'),(57,'Majlis Perbandaran Kajang','Menara MPKj Jalan Cempaka Putih, Off Jalan Semenyih, Kajang, Selangor, 43000','Maliani Binti Man','60387370112','yani@mpkj.gov.my','2026-02-24 04:43:54','2026-02-24 04:43:54'),(58,'Majlis Perbandaran Kangar','192, Persiaran Jubli Emas, Kangar, Perlis, 01000','Mohd Norfaizal Bin Abd Razak','6049762188','norfaizal@mpkangar.gov.my','2026-02-24 04:45:00','2026-02-24 04:45:00'),(59,'Majlis Perbandaran Kemaman','Jalan Air Putih	Kemaman, Terengganu, 24000','Muhammad Hilmi Bin Awang','6098597777','hilmi@mpkemaman.gov.my','2026-02-24 04:49:21','2026-02-24 04:49:21'),(60,'Majlis Perbandaran Klang','Bangunan Sultan Alam Shah, Jalan Perbandaran, Klang, Selangor, 41675','Nor Hasyimah	Binti Tamziz','60333755555','norhasyimah.tamziz@mpklang.gov.my','2026-02-24 04:51:05','2026-02-24 04:51:05'),(61,'Majlis Perbandaran Kluang','Wisma Majlis Perbandaran Kluang, Jalan Kota Tinggi, Kluang, Johor, 86000','Pn. Rafidah	Binti Rahmat','6077771401','rafidahrahmat@mpkluang.gov.my','2026-02-24 04:52:22','2026-02-24 04:52:22'),(62,'Majlis Perbandaran Kota Bharu','Jalan Hospital	Kota Bharu, Kelantan, 15000',' Khairulzani	Bin Said','6097454026','khairul@mpkb.gov.my','2026-02-24 04:53:47','2026-02-24 04:53:47'),(63,'Lembaga Perumahan Melaka','\"No.9-1 Jalan TU 49A\r\nKompleks Komersia Boulevard,\r\nTaman Tasik Utama,\"','Lembaga Perumahan Melaka','6062320556','nurulazmi@lpnm.gov.my','2026-02-24 04:54:09','2026-02-24 04:54:09'),(64,'Majlis Agama Islam & Adat Melayu Perak','Tingkat 1, Kompleks Islam Darul Ridzuan, Jalan Panglima Bukit Gantang Wahab','Azrif Bin Yahya','60195518787','azrifyahya@maiamp.gov.my','2026-02-24 04:55:16','2026-02-24 04:55:16'),(65,'Majlis Bandaraya Alor Setar','Jalan Kolam Air','En. Norzawani Bin Jusoh','6047332499','zawani@mbas.gov.my','2026-02-24 04:56:17','2026-02-24 04:56:17'),(66,'Majlis Bandaraya Ipoh','Tingkat 3, Majlis Bandaraya Ipoh, Jalan Sultan Abdul Jalil, Greentown','Abdul Rasef Bin Abdul Rani','6052083332','rasef@mbi.gov.my','2026-02-24 04:57:09','2026-02-24 04:57:09'),(67,'Majlis Perbandaran Kuala Kangsar','Pejabat Majlis Perbandaran Kuala Kangsar, Jalan Raja Chulan, Kuala Kangsar, Perak, 33000','Mohamad Salehuddin	Bin Mohamad Azranyi','6057763199','salehuddin@mpkkpk.gov.my','2026-02-24 05:06:39','2026-02-24 05:06:39'),(68,'Majlis Perbandaran Kuala Langat','Persiaran Majlis, Jalan Sultan Alam Shah, Banting, Selangor, 42700','Mohd Firdaus	Bin Zainal Abidin','60338530303','firdaus@mpkl.gov.my','2026-02-24 05:08:46','2026-02-24 05:08:46'),(69,'Majlis Perbandaran Kuala Selangor','Jalan Majlis, Kuala Selangor, Selangor, 45000','Mohd Fairuz Bin Abdul Salleh','60332891439','fairuz@mpks.gov.my','2026-02-24 05:10:05','2026-02-24 05:10:05'),(70,'Majlis Perbandaran Kubang Pasu','Kompleks Pentadbiran Daerah, Jitra, Kedah, 06000','Mohd Shafizzi	Bin Norizan','60162158817','shafizzi@mpkubangpasu.gov.my','2026-02-24 05:11:18','2026-02-24 05:11:18'),(71,'Majlis Perbandaran Kulai','Jalan Pejabat Kerajaan, Kulai, Johor, 81000','Hjh. Shahida Binti Haji Ahmad         ','6076613014','shahida@johor.gov.my','2026-02-24 05:13:59','2026-02-24 05:13:59'),(72,'Majlis Perbandaran Kulim','No. 1, Lebuh Bandar 2, Bandar Putra Kulim, Kedah, 09000','Mohammad Daniel	Bin Shukor','6044325225','mohammaddaniel@mpkk.gov.my','2026-02-24 05:15:03','2026-02-24 05:15:03'),(73,'Majlis Perbandaran Langkawi  Bandaraya Pelancongan','Majlis Perbandaran Langkawi Bandaraya Pelancongan, Langkawi, Kedah, 7000','Asura Binti Ahmad','6049666590','asura@mplbp.gov.my','2026-02-24 05:16:49','2026-02-24 05:16:49'),(74,'Majlis Perbandaran Manjung','Jalan Pinang Raja, Seri Manjung, Perak, 32040','Nurul Hafizal Bin Abdul Manap','6056898822','fizal@mpm.gov.my','2026-02-24 05:17:57','2026-02-24 05:17:57'),(75,'Majlis Perbandaran Muar','Karung Berkunci No. 516	Muar	Johor	84009','Muhammad Farhan	Bin Mohd Yunus','6069541042','noemail@yet.com','2026-02-24 05:46:12','2026-02-24 05:46:12'),(76,'Majlis Perbandaran Pontian','Jalan Alsagoff	Pontian, Johor, 82000','Mohd Fauzi	Bin Samat','6076871442','mfauzis87@mdpontian.gov.my','2026-02-24 05:49:33','2026-02-24 05:49:33'),(77,'Majlis Perbandaran Port Dickson','KM 1, Jalan Pantai, Port Dickson, Negeri Sembilan, 71009','Roslinawati	Binti Mat Zin','6066471122','watie@mppd.gov.my','2026-02-24 05:50:53','2026-02-24 05:50:53'),(78,'Majlis Perbandaran Selayang','Menara MPS, Persiaran 3, Bandar Baru Selayang, Batu Caves, Selangor, 68100','Hasmawi	Bin Mat Junit','60361265944','hasmawi.matjunit@mps.gov.my','2026-02-24 06:04:44','2026-02-24 06:04:44'),(79,'Majlis Perbandaran Sepang','Persiaran Semarak Api, Cyber 1, Cyberjaya, Selangor	63200','Rosziana	Binti Bassin','60383190212','rosziana@mpsepang.gov.my','2026-02-24 06:06:16','2026-02-24 06:06:16'),(80,'Majlis Perbandaran Sungai Petani','Menara MPSPK, Jalan Patani, Sungai Petani, Kedah 8000',' Mohd Yusri	Bin Mohamad Yusop','6044296661','myusri@mpspk.gov.my','2026-02-24 06:07:16','2026-02-24 06:07:16'),(81,'Majlis Perbandaran Taiping','Wisma Perbandaran, Jalan Taming Sari, Taiping, Perak, 34000','Hani Khoraini	Binti Jamaluddin','60195788684','hani@mptaiping.gov.my','2026-02-24 06:08:11','2026-02-24 06:08:11'),(82,'Majlis Perbandaran Teluk Intan','Jalan Speedy, Teluk Intan, Perak, 36000','Noor Hasyimah Binti Idris','6056221299','syima@mpti.gov.my','2026-02-24 06:09:18','2026-02-24 06:09:18'),(83,'Majlis Perbandaran Temerloh','Kompleks Pejabat Plaza MPT, Jalan Ahmad Shah, Temerloh, Pahang, 28000','Subhan Bin Shahudin','6092901542','subhan@mpt.gov.my','2026-02-24 06:10:25','2026-02-24 06:10:25'),(84,'Walrus Design Sdn Bhd','Block A-6-13A, Ativo Plaza, Jalan PJU 9/1 Damansara Avenue,\r\nBandar Sri Damansara, Wilayah Persekutuan Kuala Lumpur, 52200','Nurul Hayatie','60362639272','info@walrus.com.my','2026-02-26 01:15:09','2026-02-26 01:15:09'),(87,'Top IT Industries Sdn Bhd','PT4019, Blok C, Perkedaian Ladang Tok Pelam Jalan Sulltan Zainal Abidin, 20000 Kuala Terengganu, Terengganu','N/A','6096263106','alias@top-it.com.my','2026-02-26 02:13:58','2026-02-26 02:13:58'),(88,'TOA Electronics (M) Sdn Bhd','3rd Floor, Wisma Kemajuan, \r\nNo. 2, Jalan 19/1B\"	Petaling Jaya	Selangor	46300','Jason','60379601128','sales@toamys.com.my','2026-02-26 02:15:10','2026-02-26 02:15:10'),(90,'Sysmex Malaysia Sdn Bhd','15, Jalan PJS 7/21, PJS 7	Subang Jaya	Selangor	47500','Zaiton	Abdullah','60356371788','sysmex@sysmex.com.my','2026-02-26 02:17:16','2026-02-26 02:17:16'),(91,'Stream Tech Sdn Bhd','Unit B-2-09, Block B, Oasis Square, No. 2, Jalan PJU 1A/7A, Oasis Damansara, Petaling Jaya, Selangor, 47301','anuar@streamtech.asia,fatinnazihah69@gmail.com','60378316616','anuar@streamtech.asia','2026-02-26 02:19:22','2026-02-26 02:19:22'),(93,'Spectrum Edge Sdn Bhd','D2-U3A-10, Solaris Dutamas, No. 1, Jalan Dutamas, Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur','Joanne	Lum','60362079392','joannelum@spectrum-edge.com','2026-02-26 02:21:51','2026-02-26 02:21:51'),(94,'SCHMIDT BioMedTech','5th Floor, Wisma Tecna, 18A, Jalan 51a/223, Seksyen 51a, 46100 Petaling Jaya, Selangor','N/A','60378449000','cwwong@schmidtbmt.com','2026-02-26 02:23:46','2026-02-26 02:23:46'),(95,'Sangfor Technologies (Malaysia) Sdn Bhd','Suite 11.01 & 11.02 , Level 11, Centrepoint North, Lingkaran Syed Putra, Mid Valley City, 59200 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur','N/A','60123397062','ros.jaafar@sangfor.com','2026-02-26 02:25:06','2026-02-26 02:25:06'),(96,'PS Terminal Tawau','Jalan Tanjung Batu Laut, P.O Box 731	Tawau, Sabah, 91008','Damis','6089776903','damis@psterminal.com','2026-02-26 02:26:19','2026-02-26 02:26:19'),(97,'PS Terminal Bintulu','Km22, Jalan Tanjong Kidurong, 97012, 97000 Bintulu, Sarawak','N/A','6086255442','dominic@psterminal.com','2026-02-26 02:28:24','2026-02-26 02:28:24'),(98,'PS Pipeline Sdn Bhd','Klang Valley Distribution Terminal (KVDT), KM 18, Jalan Dengkil-Puchong, Dengkil, Selangor, 43800','Abdul Rahman Bin Zakaria','60388943333','rahman@pspipeline.com.my','2026-02-26 02:29:31','2026-02-26 02:29:31'),(99,'Precision Computer (M) Sdn Bhd','D-55-2, Jalan C180/1\r\nDataran C180\"	Cheras	Selangor	43200','Safira','60167256662','safira@server2u.net','2026-02-26 02:31:02','2026-02-26 02:31:02');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_experiences`
--

DROP TABLE IF EXISTS `company_experiences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_experiences` (
  `experience_id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `client_id` int DEFAULT NULL,
  `agency_name` varchar(255) NOT NULL,
  `contract_name` varchar(255) NOT NULL,
  `contract_year` year NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `source_soa_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`experience_id`),
  KEY `category_id` (`category_id`),
  KEY `client_id` (`client_id`),
  KEY `source_soa_id` (`source_soa_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `company_experiences_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `experience_categories` (`category_id`),
  CONSTRAINT `company_experiences_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE SET NULL,
  CONSTRAINT `company_experiences_ibfk_3` FOREIGN KEY (`source_soa_id`) REFERENCES `client_soa` (`soa_id`) ON DELETE SET NULL,
  CONSTRAINT `company_experiences_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_experiences`
--

LOCK TABLES `company_experiences` WRITE;
/*!40000 ALTER TABLE `company_experiences` DISABLE KEYS */;
/*!40000 ALTER TABLE `company_experiences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `document_id` int NOT NULL AUTO_INCREMENT,
  `document_type` enum('Receipt','Invoice','Warranty','Claim') COLLATE utf8mb4_general_ci NOT NULL,
  `reference_id` int NOT NULL,
  `reference_type` enum('Client','Supplier','Staff','SOA') COLLATE utf8mb4_general_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uploaded_by` int NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`document_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `experience_categories`
--

DROP TABLE IF EXISTS `experience_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `experience_categories`
--

LOCK TABLES `experience_categories` WRITE;
/*!40000 ALTER TABLE `experience_categories` DISABLE KEYS */;
INSERT INTO `experience_categories` VALUES (1,'Antivirus','2026-02-23 02:18:32'),(2,'Firewall','2026-02-23 02:18:32'),(3,'SPA','2026-02-23 02:18:32'),(4,'Active Directory','2026-02-23 02:18:32'),(5,'Hardware Provider','2026-02-23 02:18:32'),(6,'Troubleshoot','2026-02-23 02:18:32');
/*!40000 ALTER TABLE `experience_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_categories`
--

DROP TABLE IF EXISTS `inventory_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_categories`
--

LOCK TABLES `inventory_categories` WRITE;
/*!40000 ALTER TABLE `inventory_categories` DISABLE KEYS */;
INSERT INTO `inventory_categories` VALUES (1,'Desktop / PC','Desktop / PC only','2025-04-16 07:03:26','2025-04-16 07:03:26'),(2,'Laptop','Laptop only','2025-04-16 07:20:47','2025-04-16 07:20:47'),(3,'Server','sdjdjd','2025-04-21 01:53:56','2025-04-21 01:53:56'),(4,'Other Hardware','Keyboard, Switch, Motherboard and etc','2025-04-22 02:04:50','2025-04-22 02:04:50');
/*!40000 ALTER TABLE `inventory_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `serial_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `model_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `status` enum('Available','Assigned','Maintenance','Disposed') COLLATE utf8mb4_general_ci DEFAULT 'Available',
  `location` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `category_id` (`category_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`category_id`),
  CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  CONSTRAINT `inventory_items_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_items`
--

LOCK TABLES `inventory_items` WRITE;
/*!40000 ALTER TABLE `inventory_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_maintenance`
--

DROP TABLE IF EXISTS `inventory_maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_maintenance` (
  `maintenance_id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `maintenance_date` date NOT NULL,
  `maintenance_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `performed_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `next_maintenance_date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`maintenance_id`),
  KEY `item_id` (`item_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `inventory_maintenance_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`),
  CONSTRAINT `inventory_maintenance_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_maintenance`
--

LOCK TABLES `inventory_maintenance` WRITE;
/*!40000 ALTER TABLE `inventory_maintenance` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_maintenance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_transactions` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `transaction_type` enum('Purchase','Assignment','Return','Maintenance','Disposal') COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int DEFAULT '1',
  `from_status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text COLLATE utf8mb4_general_ci,
  `performed_by` int NOT NULL,
  PRIMARY KEY (`transaction_id`),
  KEY `item_id` (`item_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `performed_by` (`performed_by`),
  CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`),
  CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`staff_id`),
  CONSTRAINT `inventory_transactions_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `staff` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_transactions`
--

LOCK TABLES `inventory_transactions` WRITE;
/*!40000 ALTER TABLE `inventory_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mileage_rates`
--

DROP TABLE IF EXISTS `mileage_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mileage_rates` (
  `rate_id` int NOT NULL AUTO_INCREMENT,
  `vehicle_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `km_threshold` int NOT NULL,
  `rate_per_km` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rate_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mileage_rates`
--

LOCK TABLES `mileage_rates` WRITE;
/*!40000 ALTER TABLE `mileage_rates` DISABLE KEYS */;
INSERT INTO `mileage_rates` VALUES (1,'Car',500,0.80,'2025-05-07 02:12:17','2025-05-07 02:12:17'),(2,'Car',999999,0.50,'2025-05-07 02:12:17','2025-05-07 02:12:17'),(3,'Motorcycle',999999,0.50,'2025-05-07 02:12:17','2025-05-07 02:12:17');
/*!40000 ALTER TABLE `mileage_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outstation_applications`
--

DROP TABLE IF EXISTS `outstation_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `outstation_applications` (
  `application_id` int NOT NULL AUTO_INCREMENT,
  `application_number` varchar(50) NOT NULL,
  `staff_id` int NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `purpose_details` text NOT NULL,
  `destination` varchar(255) NOT NULL,
  `departure_date` date NOT NULL,
  `departure_time` time DEFAULT NULL,
  `return_date` date NOT NULL,
  `return_time` time DEFAULT NULL,
  `total_nights` int NOT NULL DEFAULT '0',
  `is_claimable` tinyint(1) NOT NULL DEFAULT '0',
  `transportation_mode` varchar(100) NOT NULL,
  `estimated_cost` decimal(10,2) DEFAULT '0.00',
  `accommodation_details` text,
  `remarks` text,
  `status` enum('Pending','Approved','Rejected','Cancelled','Completed') NOT NULL DEFAULT 'Pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`application_id`),
  UNIQUE KEY `application_number` (`application_number`),
  KEY `fk_staff` (`staff_id`),
  KEY `fk_approver` (`approved_by`),
  KEY `idx_status` (`status`),
  KEY `idx_departure_date` (`departure_date`),
  KEY `idx_is_claimable` (`is_claimable`),
  KEY `idx_application_number` (`application_number`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_outstation_approver` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_outstation_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outstation_applications`
--

LOCK TABLES `outstation_applications` WRITE;
/*!40000 ALTER TABLE `outstation_applications` DISABLE KEYS */;
INSERT INTO `outstation_applications` VALUES (1,'OSL-2025-9682',13,'Training/Workshop','Training Cloud++','Kuala Lumpur','2025-11-27','14:00:00','2025-11-28','14:00:00',1,1,'Company Vehicle',0.00,'training in KL testing','training','Approved',3,'2025-11-26 03:06:11',NULL,'2025-11-26 03:04:59','2025-11-26 03:06:11'),(2,'OSL-2025-5984',12,'Project Work','testing testing','Melaka','2025-11-28','11:29:00','2025-11-28','23:24:00',0,0,'Personal Vehicle',0.00,'testing testing hotel','testing testing remarks','Rejected',6,'2025-11-26 03:26:30','cancel','2025-11-26 03:24:32','2025-11-26 03:26:30'),(6,'OSL-2026-7199',12,'Client Meeting','Meeting SSO Project with Majlis Perbandaran Alor Gajah','Alor Gajah, Melaka','2026-01-22','07:00:00','2026-01-22','16:00:00',0,0,'Company Vehicle',60.00,'none','none','Pending',NULL,NULL,NULL,'2026-01-21 04:44:46','2026-01-21 04:44:46'),(7,'OSL-2026-1541',12,'Maintenance','Preventive Maintenance Q1','Bintulu','2026-03-30','17:00:00','2026-04-03','22:00:00',4,1,'Flight',0.00,'Fairfield by Marriot','','Pending',NULL,NULL,NULL,'2026-04-07 00:03:20','2026-04-07 00:03:20'),(8,'OSL-2026-3322',15,'Maintenance','- PM IT Infra and End User\r\n- Migrate old server to new server','PSTB','2026-03-30','13:00:00','2026-04-03','20:00:00',4,1,'Flight',0.00,'Fairfield by Marriott Bintulu Paragon','','Pending',NULL,NULL,NULL,'2026-04-07 00:17:10','2026-04-07 00:17:10');
/*!40000 ALTER TABLE `outstation_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outstation_claims`
--

DROP TABLE IF EXISTS `outstation_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `outstation_claims` (
  `claim_id` int NOT NULL AUTO_INCREMENT,
  `application_id` int NOT NULL,
  `staff_id` int NOT NULL,
  `claim_date` date NOT NULL,
  `claim_status` enum('Submitted','Approved','Rejected','Paid') NOT NULL DEFAULT 'Submitted',
  `claim_amount` decimal(10,2) DEFAULT '0.00',
  `actual_expenses` decimal(10,2) DEFAULT '0.00',
  `supporting_documents` text,
  `notes` text,
  `processed_by` int DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`claim_id`),
  KEY `fk_claim_application` (`application_id`),
  KEY `fk_claim_staff` (`staff_id`),
  KEY `fk_claim_processor` (`processed_by`),
  KEY `idx_claim_status` (`claim_status`),
  CONSTRAINT `fk_claim_application` FOREIGN KEY (`application_id`) REFERENCES `outstation_applications` (`application_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_claim_processor` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_claim_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outstation_claims`
--

LOCK TABLES `outstation_claims` WRITE;
/*!40000 ALTER TABLE `outstation_claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `outstation_claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outstation_settings`
--

DROP TABLE IF EXISTS `outstation_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `outstation_settings` (
  `setting_id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outstation_settings`
--

LOCK TABLES `outstation_settings` WRITE;
/*!40000 ALTER TABLE `outstation_settings` DISABLE KEYS */;
INSERT INTO `outstation_settings` VALUES (1,'minimum_nights_claimable','2','Minimum number of nights required to qualify for outstation leave claim',NULL,'2025-11-26 08:39:03'),(2,'default_allowance_per_day','100.00','Default daily allowance amount in RM',NULL,'2025-11-26 02:30:02'),(3,'require_manager_approval','1','Whether applications require manager approval (1=yes, 0=no)',NULL,'2025-11-26 02:30:02'),(4,'auto_approve_days','0','Number of days after which pending applications are auto-approved (0=disabled)',NULL,'2025-11-26 02:30:02');
/*!40000 ALTER TABLE `outstation_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `po_id` int NOT NULL,
  `description` varchar(500) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `po_id` (`po_id`),
  CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_orders` (
  `po_id` int NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `status` enum('Draft','Approved','Partially Invoiced','Closed','Received','Cancelled') DEFAULT 'Draft',
  `subtotal` decimal(12,2) DEFAULT '0.00',
  `tax_amount` decimal(12,2) DEFAULT '0.00',
  `total_amount` decimal(12,2) DEFAULT '0.00',
  `notes` text,
  `approved_by` int DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `supplier_invoice_number` varchar(100) DEFAULT NULL,
  `supplier_invoice_date` date DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`po_id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `supplier_id` (`supplier_id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`),
  CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soa`
--

DROP TABLE IF EXISTS `soa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `soa` (
  `soa_id` int NOT NULL AUTO_INCREMENT,
  `account_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `client_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `terms` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `purchase_date` date NOT NULL,
  `issue_date` date NOT NULL,
  `po_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `invoice_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `balance_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Paid','Overdue') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`soa_id`),
  KEY `client_id` (`client_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `soa_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  CONSTRAINT `soa_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  CONSTRAINT `soa_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soa`
--

LOCK TABLES `soa` WRITE;
/*!40000 ALTER TABLE `soa` DISABLE KEYS */;
/*!40000 ALTER TABLE `soa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soa_payments`
--

DROP TABLE IF EXISTS `soa_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `soa_payments` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `soa_id` int NOT NULL,
  `payment_date` date NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Bank Transfer','Cash','Cheque','Online Payment','Credit Card','Other') NOT NULL DEFAULT 'Bank Transfer',
  `payment_reference` varchar(100) DEFAULT NULL,
  `notes` text,
  `recorded_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `soa_id` (`soa_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `soa_payments_ibfk_1` FOREIGN KEY (`soa_id`) REFERENCES `client_soa` (`soa_id`) ON DELETE CASCADE,
  CONSTRAINT `soa_payments_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soa_payments`
--

LOCK TABLES `soa_payments` WRITE;
/*!40000 ALTER TABLE `soa_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `soa_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `staff_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `department` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `position` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (3,'admin','$2y$10$Uvaj6CCWMBgTdVgx4eqGBuOMTJKvSKr1OqRvFmZfu92toC5ONlRfe','System Administrator','admin@example.com','IT','Admin','2025-04-14 07:57:32','2025-09-09 17:02:43'),(6,'Syafinaz','$2y$10$zOkBh6KC0ltmzsxn/Ac0x.vAjvu/82NDHz3hmqzdoNtP9EmwTFqTe','Nurul Syafinaz binti Rosli','syafinaz.rosli@kyrolsecuritylabs.com','Operation Manager','Admin','2025-04-14 08:05:29','2026-03-26 01:51:12'),(7,'staff','$2y$10$YOCX1M268FeR4vHVReEFru9LrMLZ65kptjlTRzq3TKnd6YovpWIyK','KYROL staff','staff@kyrolsecuritylabs.com','IT Department','Staff','2025-04-17 04:13:09','2025-11-26 03:11:59'),(10,'Khairol','$2y$10$XiXpIiscy/PJpp5l7HYCs.2jDE3A2rJ7Lx9Lm0CfFLFQwMHnQlQai','Khairol Shapawi bin Abdul Karim','khairol@kyrolsecuritylabs.com','Chief Operation Executive','Manager','2025-05-08 03:07:12','2025-09-09 17:00:00'),(11,'Elle','$2y$10$oSK/K2LTHVYGTf6AQVhORuJID/vxl6oITB.SWBNIyhXKFNialB9Wi','Nur Sarah Ellyana','elle@kyrolsecuritylabs.com','Sales Director','Admin','2025-09-09 17:02:14','2025-11-26 03:20:53'),(12,'Iskhandar','$2y$10$I.aJ15MKg8joEdZFSq8nE.tBSaC5g5J0q3xLSCDL/E67BmmW9octa','Iskhandar Shah bin Mohamad Yunus','iskhandar@kyrolsecuritylabs.com','System Engineer','Admin','2025-09-09 17:06:18','2026-03-29 08:54:59'),(13,'alyazuan','$2y$10$/7qTtDP1nT8n7j7N6jWk3.OCNM8eUW.cfq4.HlrwQ0Z7v7Uo7MmGe','Alya Maisarah','alya@kyrolsecuritylabs.com','System Engineer','Admin','2025-11-26 02:47:05','2026-02-24 04:01:31'),(14,'Adawiyah','$2y$10$KqTEZt4t/5aKfNDzo8hazOIlOKbswDaw4rDjwl6y7I0NXoQI9RdKi','Nur Adawiyah','adawiyah@kyrolsecuritylabs.com','Network Engineer','Admin','2025-11-26 07:07:03','2026-03-17 01:38:48'),(15,'Adib','$2y$10$jdDwgtuX2dK8r.3a.2tbS.MnTJjwSlHWfDGeXm7Liz7l3jhjrEGNO','Adib Fahmi','adib@kyrolsecuritylabs.com','Network Engineer','Staff','2025-11-26 07:08:52','2025-11-26 07:08:52'),(16,'Irsyad','$2y$10$AQCThCVZ1ufsBVJd1TUbXe9WFYJwSEz75.Cjmf39dSx3mAMKb3pSO','Wan Muhammad Irsyad','irsyad@kyrolsecuritylabs.com','Network Engineer','Staff','2025-11-26 07:11:54','2025-11-26 07:11:54'),(18,'Syazreen','$2y$10$ZBE5G94uXXnPw7FaBn3eCOkQWoKnXZFzJgZLHO.BF7mt.4/l8y6yu','Nur Syazreen Aifa','syazreen@kyrolsecuritylabs.com','IT Technical','Staff','2025-11-26 07:15:24','2025-11-26 07:15:24'),(19,'Abu','$2y$10$NWROV2wiFsQpM5CnekepNuzcOfO9nbG6dpN32Pc.yvyILFmohQygO','Abu Hafizuddin bin Abu Hassan','abu@kyrolsecuritylabs.com','IT Technical','Staff','2026-04-07 00:11:36','2026-04-07 00:11:36'),(20,'Arif','$2y$10$4GNlLuNqU5B9UZlAefMZ6u0eKLK9VtEqZmf1XhrFN1N1GnyG8OHPq','Muhammad Arif Danial bin Mohd Anuar','arif@kyrolsecuritylabs.com','IT Technical','Staff','2026-04-07 00:14:02','2026-04-07 00:14:02'),(21,'Syamir','$2y$10$RPbPrNy0hziCtG6M4354tugxtit9g9UZd3yJkr6fyPQhXNjnTkjIW','Abdul Syamir Iqmal bin Abdul Salim','syamir@kyrolsecuritylabs.com','IT Technical','Staff','2026-04-07 00:14:52','2026-04-07 00:14:52');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statement_soa_items`
--

DROP TABLE IF EXISTS `statement_soa_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statement_soa_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `statement_id` int NOT NULL,
  `soa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_statement_soa` (`statement_id`,`soa_id`),
  KEY `soa_id` (`soa_id`),
  CONSTRAINT `statement_soa_items_ibfk_1` FOREIGN KEY (`statement_id`) REFERENCES `client_statements` (`statement_id`) ON DELETE CASCADE,
  CONSTRAINT `statement_soa_items_ibfk_2` FOREIGN KEY (`soa_id`) REFERENCES `client_soa` (`soa_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statement_soa_items`
--

LOCK TABLES `statement_soa_items` WRITE;
/*!40000 ALTER TABLE `statement_soa_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `statement_soa_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_soa`
--

DROP TABLE IF EXISTS `supplier_soa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_soa` (
  `soa_id` int NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `supplier_id` int NOT NULL,
  `po_id` int DEFAULT NULL,
  `issue_date` date NOT NULL,
  `payment_due_date` date NOT NULL,
  `purchase_description` text COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Pending','Paid','Overdue') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`soa_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `created_by` (`created_by`),
  KEY `po_id` (`po_id`),
  CONSTRAINT `supplier_soa_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  CONSTRAINT `supplier_soa_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`),
  CONSTRAINT `supplier_soa_ibfk_3` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_soa`
--

LOCK TABLES `supplier_soa` WRITE;
/*!40000 ALTER TABLE `supplier_soa` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_soa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `supplier_id` int NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `address` text COLLATE utf8mb4_general_ci NOT NULL,
  `pic_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `pic_contact` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `pic_email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (5,'Fortesys Distribution Sdn Bhd','Not 26, Jalan Puteri 5/5, Bandar Puteri, 47100 Puchong, Selangor','Crystal Leong','+6012-603 0628','crystal.leong@fortesys.net','2026-03-29 09:00:13','2026-04-01 06:21:24'),(7,'Jrsys Sdn Bhd','D-G-06, D-1-06,Ritze Perdana Business Center, No 5. Jalan PJU 8/2, Bandar Damansara Perdana','not stated','+603-77221941','info@jrsys.com.my','2026-03-29 09:01:56','2026-04-01 06:21:33'),(8,'OGX Network Sdn Bhd','No. 18 & 20, Jalan Astaka U8/84A.\r\nTaman Perindustrian Bukit Jelutong\r\nSeksyen U8, 40150 Shah Alam, Selangor D.E','Amirah Mustaffa','+6018-3260995','amirah.mustaffa@ogx.com.my','2026-03-29 09:02:56','2026-04-01 06:21:41'),(9,'Parts Avenue Sdn Bhd','No 5-12 Jalan Usj 1/1C Regalia Business Centre, Taman Subang Mewah','Alan Ko','+6011-1012 6326','alan@parts-avenue.com','2026-03-29 09:03:31','2026-04-01 06:20:05'),(10,'PD Solutions Sdn Bhd','1-04-04, e-Gate, Lebuh Tunku Kudin 2','not stated','+604-6563008','notstated@mail.com','2026-03-29 09:04:15','2026-04-01 06:21:51'),(11,'Polinta Cd-Dvd Manufacturer Sdn Bhd','Lot 1872, Kawasan Perindustrian Balakong, Office 3, Level 1, Resource Centre','Joan','+603-89619999','info@polinta.com','2026-03-29 09:04:54','2026-04-01 06:22:04'),(12,'Precision Computer (M) Sdn Bhd','D-55-2, Jalan C180/1 Dataran C180','Safira','+6016-7256662','safira@server2u.net','2026-03-29 09:05:37','2026-04-01 06:22:13'),(14,'Spectrum Edge Sdn Bhd','D2-U3A-10, Solaris Dutamas, No. 1, Jalan Dutamas','Joanne Lum','+60362079392','joannelum@spectrum-edge.com','2026-03-29 09:07:01','2026-04-01 06:21:01'),(15,'Strategic Alliance Sdn Bhd','44-1 Jalan BPP 8/4,\r\nPusat Bandar Putra Permai','Nurul Huda Zakaria','+603-89662424','nurul@sa.com.my','2026-03-29 09:07:55','2026-04-01 06:22:36'),(16,'TENRYU (M) Sdn Bhd','7, Jalan USJ 21/10, Taman Indah Subang Uep, \r\n47640 Subang Jaya, Selangor','not stated','+603-8020 7815','info@tenryu.com.my','2026-03-29 09:08:34','2026-04-01 06:22:48'),(17,'VSTECS Astar Sdn Bhd','Lot 3, Jalan Teknologi 3/5,\r\nTaman Sains Selangor, Kota Damansara,\r\n47810 Petaling Jaya, Selangor, Malaysia.','Lee Pei Yun ; Kelly Yap','+6018-313 1886','pylee@vstecs.com.my','2026-03-29 09:09:08','2026-04-01 06:22:59'),(18,'VSTECS Pericomp Sdn Bhd','Lot 3, Jalan Teknologi 3/5,\r\nTaman Sains Selangor, Kota Damansara,\r\n47810 Petaling Jaya, Selangor, Malaysia.','Nurul Hidayah','+603-62868313','nurulhidayah@vstecs.com.my','2026-03-29 09:09:40','2026-04-01 06:19:33'),(19,'Ingram Micro Malaysia Sdn Bhd','Lot 4A, 4th Floor, Wisma Academy, Jalan 19/1, Seksyen 19, 46300 Petaling Jaya, Selangor','Candace Wong','+6017-255 9188','Candace.Wong@ingrammicro.com','2026-04-01 02:21:09','2026-04-01 02:21:09'),(20,'Enfrasys Solutions Sdn Bhd','DF2-15-01 (Unit 3), Level 15, Persoft Tower,\r\n6B, Persiaran Tropicana, 47410 Petaling Jaya,\r\nSelangor Darul Ehsan.','Natasha Jabar','+603-7498 1525','siti.nurnatasha@enfrasys.com','2026-04-01 06:10:59','2026-04-01 06:19:40'),(21,'Awesome Technology Resources','Unit B2-2-3 Solaris Dutamas 1','Eddie Lee','+603-6268896','sales@computermalaysia.com.my','2026-04-01 07:21:09','2026-04-01 07:21:09'),(22,'Enetech Sdn Bhd','No. 32A, Tingkat, 1, \r\nJalan Kota Raja J 27/J, Seksyen 27, \r\n40400 Shah Alam, Selangor','Account','+603-51029093','account@enetech.my','2026-04-01 07:25:41','2026-04-01 07:25:41'),(23,'Ezytronic Sdn Bhd','29, Jalan PPU 2A, \r\nTaman Perindustrian Puchong Utama, \r\n47100 Puchong, Selangor','Charis Cheng','+603-80237639','sales@ezytronic.com','2026-04-01 07:27:33','2026-04-01 07:27:33'),(24,'Fortesys Distribution Sdn Bhd','26, Jalan Puteri 5/5, \r\nBandar Puteri, \r\n47100 Puchong, Selangor','Crystal Lee Wan Ling','+603-80624045','crystal@fortesys.net','2026-04-01 07:29:51','2026-04-01 07:29:51'),(25,'GCM Technologies Sdn Bhd','D20-1, Jalan 9/116B, Sri Desa Entrepreneur Park, \r\n58200 Kuala Lumpur, \r\nWilayah Persekutuan Kuala Lumpur','Siti Noor Illiani','+603-7983 699','gcm@gcmsb.com.my','2026-04-01 07:34:59','2026-04-01 07:35:40'),(26,'Sangfor Technologies (Malaysia) Sdn Bhd','47-10 The Boulevard Offices, Mid Valley City, \r\nLingkaran Syed Putra, Mid Valley City, \r\n59200 Kuala Lumpur, \r\nWilayah Persekutuan Kuala Lumpur','Rosshahidah Jaafar','+6012-3397062','ros.jaafar@sangfor.com','2026-04-02 02:25:30','2026-04-02 02:25:30'),(27,'Hayamim Ava Sdn Bhd','Lot 27, Jalan SBC 5, Jln Sri Batu Caves, \r\nIndustrial Park, \r\n68100 Batu Caves, Selangor','Hayamim Ava','+603-6189 3213','sales@hayamim.com.my','2026-04-02 02:27:05','2026-04-02 02:27:05'),(28,'Digital Quest Sdn Bhd','Zenith Corporate Park, \r\n59-2 Block E, \r\nJalan SS 7/26, \r\n47301 Petaling Jaya, Selangor','Jaclyn','+603-79311273','sales@digitalquest.asia','2026-04-02 02:29:07','2026-04-02 02:29:07'),(29,'Kaysix Sdn Bhd','C-09-01 iTech Tower Jalan Impact, Cyber 6,\r\n63000 Cyberjaya, Selangor','Syafinaz Rosli','+603-86855032','info@kaysix.com.my','2026-04-02 02:30:32','2026-04-02 02:30:32');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-12 12:00:01
