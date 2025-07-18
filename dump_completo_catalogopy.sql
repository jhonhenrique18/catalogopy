-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: catalogo_graos
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `categories_ibfk_1` (`parent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (16,'TÔö£┬«s','','',NULL,1,'2025-05-22 18:04:11','2025-05-22 18:04:11'),(17,'Suplementos','','',NULL,1,'2025-05-22 18:04:30','2025-05-22 18:04:30'),(18,'semillas','','',NULL,1,'2025-05-22 18:04:48','2025-05-22 18:04:48'),(19,'Harinas','','',NULL,1,'2025-05-22 18:05:20','2025-05-22 18:05:20'),(20,'Frutos Secos','','',NULL,1,'2025-05-22 18:05:54','2025-05-22 18:05:54'),(22,'Especiais','','',NULL,1,'2025-05-22 18:06:19','2025-05-22 18:06:19'),(27,'Edulcorantes','','',NULL,1,'2025-05-22 18:19:26','2025-05-22 18:19:26'),(28,'Chocolate','','',NULL,1,'2025-05-22 18:19:37','2025-05-22 18:19:37'),(31,'Castanhas','','',NULL,1,'2025-05-22 18:21:24','2025-05-22 18:21:24'),(32,'cacao','','uploads/categorias/category_682f73e495e84.jpg',NULL,1,'2025-05-22 18:21:33','2025-05-22 18:58:44'),(33,'Aceites','','uploads/categorias/category_682f7398dee51.png',NULL,1,'2025-05-22 18:21:46','2025-05-22 18:59:37'),(34,'Cereales','','',NULL,1,'2025-05-22 20:36:48','2025-05-22 20:36:48');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exchange_rate`
--

DROP TABLE IF EXISTS `exchange_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exchange_rate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rate` decimal(10,4) NOT NULL DEFAULT 1420.0000,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exchange_rate`
--

LOCK TABLES `exchange_rate` WRITE;
/*!40000 ALTER TABLE `exchange_rate` DISABLE KEYS */;
INSERT INTO `exchange_rate` VALUES (1,1420.0000,'2025-06-09 18:13:00','Sistema - CorreÔö£┬║Ôö£├║o da CorreÔö£┬║Ôö£├║o');
/*!40000 ALTER TABLE `exchange_rate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `is_wholesale` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,69,'AcaÔö£┬í en polvo',10,130.00,1300.00,1,'2025-06-09 14:25:01'),(2,1,112,'70% chispas de chocolate amargo',1,200.00,200.00,0,'2025-06-09 14:25:01'),(3,1,118,'Cubo aceite de coco virgen extra 3,2 Santo Ôö£├┤leo',2,265.00,530.00,0,'2025-06-09 14:25:01'),(4,1,22,'CÔö£Ôòærcuma',5,21.00,105.00,0,'2025-06-09 14:25:01'),(5,1,109,'Damasco Turco Dulce',1,65.00,65.00,0,'2025-06-09 14:25:01'),(6,1,113,'DÔö£├¡til de cacao 70%',1,200.00,200.00,0,'2025-06-09 14:25:01'),(7,1,103,'Gelatina en polvo sin sabor',1,200.00,200.00,0,'2025-06-09 14:25:01'),(8,1,85,'Ginseng de raÔö£┬íz rayada',6,190.00,1140.00,0,'2025-06-09 14:25:01'),(10,3,1,'Anis Estrelado',12,71000.00,852000.00,1,'2025-06-09 14:57:51'),(11,4,168,'Hojuelas de coco rallado',11,85200.00,937200.00,1,'2025-06-09 18:06:02'),(12,4,69,'AcaÔö£┬í en polvo',5,184600.00,923000.00,1,'2025-06-09 18:06:02'),(13,4,14,'Boldo Chileno',5,52540.00,262700.00,1,'2025-06-09 18:06:02'),(14,4,58,'Comino en grano',10,38340.00,383400.00,1,'2025-06-09 18:06:02'),(15,5,69,'AcaÔö£┬í en polvo',5,184600.00,923000.00,1,'2025-06-09 18:20:38'),(20,7,168,'Hojuelas de coco rallado',1,92300.00,92300.00,0,'2025-06-27 20:52:37'),(21,7,1,'Anis Estrelado',1,85200.00,85200.00,0,'2025-06-27 20:52:37'),(22,7,95,'Ajo granulado',2,142000.00,284000.00,0,'2025-06-27 20:52:37'),(23,7,59,'Albahaca en polvo',1,28400.00,28400.00,0,'2025-06-27 20:52:37'),(24,8,69,'AcaÔö£┬í en polvo',10,184600.00,1846000.00,1,'2025-07-10 01:08:25'),(25,8,3,'AnÔö£┬ís',75,39760.00,2982000.00,1,'2025-07-10 01:08:25'),(26,8,169,'Mani sim piel y sin sal',2,0.00,0.00,1,'2025-07-10 01:08:25'),(27,8,168,'Hojuelas de coco rallado',10,85200.00,852000.00,1,'2025-07-10 01:08:25'),(28,9,168,'Hojuelas de coco rallado',11,85200.00,937200.00,1,'2025-07-10 01:42:03'),(29,9,1,'Anis Estrelado',10,71000.00,710000.00,1,'2025-07-10 01:42:03'),(30,9,169,'Mani sim piel y sin sal',1,0.00,0.00,1,'2025-07-10 01:42:03'),(31,10,168,'Hojuelas de coco rallado',1,92300.00,92300.00,0,'2025-07-12 00:30:24'),(32,10,69,'AcaÔö£┬í en polvo',10,184600.00,1846000.00,1,'2025-07-12 00:30:24'),(33,10,134,'Alfafa',1,62394.80,62394.80,0,'2025-07-12 00:30:24'),(34,10,17,'Aceite de coco virgen extra copra 200 ml',1,42600.00,42600.00,0,'2025-07-12 00:30:24'),(35,10,56,'Cilantro en polvo',1,35500.00,35500.00,0,'2025-07-12 00:30:24'),(36,10,32,'Xilitol Cristal',1,113600.00,113600.00,0,'2025-07-12 00:30:24'),(37,10,77,'Vinagre OrgÔö£├│nico de 4 EstaÔö£┬║Ôö£├ües 500ML',1,34080.00,34080.00,0,'2025-07-12 00:30:24');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_logs`
--

DROP TABLE IF EXISTS `order_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `admin_user` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_logs`
--

LOCK TABLES `order_logs` WRITE;
/*!40000 ALTER TABLE `order_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `customer_address` text NOT NULL,
  `customer_city` varchar(100) NOT NULL,
  `customer_reference` varchar(255) DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_weight` decimal(8,2) NOT NULL DEFAULT 0.00,
  `shipping` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pendente','contatado','confirmado','enviado','entregue','cancelado') DEFAULT 'pendente',
  `whatsapp_sent` tinyint(1) DEFAULT 1,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `contacted_at` timestamp NULL DEFAULT NULL,
  `contacted_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_date` (`created_at`),
  KEY `idx_orders_customer` (`customer_name`,`customer_phone`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'ORD-20250609-1723','jhonatan','jhonatan@grupo-dip.com','+595 98 428 770','rua teste','foz do iguaÔö£┬║u','','',447.75,27.00,4848.59,5296.34,'enviado',1,NULL,'2025-06-09 14:25:01','2025-06-09 18:10:32','2025-06-09 14:25:49','Admin'),(3,'ORD-20250609-2959','Carlos Mendoza','carlos.mendoza@empresa.com','+595123456789','Ruta Transchaco Km 15, San Lorenzo (Perto da estaÔö£┬║Ôö£├║o de serviÔö£┬║o)','San Lorenzo','Perto da estaÔö£┬║Ôö£├║o de serviÔö£┬║o','Entrega urgente para negocio',102000.00,12.00,3060000.00,3162000.00,'pendente',1,NULL,'2025-06-09 14:57:51','2025-06-09 18:10:32',NULL,NULL),(4,'ORD-20250609-7629','Carlos Silva Testador','carlos.testador@email.com','+595 98 112 345','Avenida EspaÔö£ÔûÆa 1254 c/ Mcal. LÔö£Ôöépez','AsunciÔö£Ôöén','Frente ao Shopping del Sol, edificio azul','TESTE COMPLETO DO SISTEMA - Precisa entregar pela manhÔö£├║. Produtos para revenda.',300050.00,31.00,7905000.00,8205050.00,'pendente',1,NULL,'2025-06-09 18:06:02','2025-06-09 18:10:32',NULL,NULL),(5,'ORD-20250609-2369','Maria Silva - TESTE VALORES CORRIGIDOS','','+595 98 765 432','Av. Mariscal LÔö£Ôöépez 123','AsunciÔö£Ôöén','','├ö┬ú├á TESTE CRÔö£├¼TICO APROVADO - Valores corrigidos com sucesso!',923000.00,5.00,7500.00,930500.00,'pendente',1,NULL,'2025-06-09 18:20:38','2025-06-09 18:20:38',NULL,NULL),(7,'ORD-20250627-6332','jhonatan','jhonatan@gmail.com','45984287709','rua teste','teste','teste','tese',489900.00,5.00,7500.00,497400.00,'pendente',1,NULL,'2025-06-27 20:52:37','2025-06-27 20:52:37',NULL,NULL),(8,'ORD-20250709-3120','Juan Perez','','+595981234567','Av. Espa├▒a 123','Asunci├│n','','',5680000.00,97.00,145500.00,5825500.00,'pendente',1,NULL,'2025-07-10 01:08:25','2025-07-10 01:08:25',NULL,NULL),(9,'ORD-20250709-4216','Jo├úo Silva','','+595991234567','Av. Test 123','Asunci├│n','','',1647200.00,22.00,0.00,1647200.00,'pendente',1,NULL,'2025-07-10 01:42:03','2025-07-10 01:42:03',NULL,NULL),(10,'ORD-20250711-9816','jhonatan','','45984287709','rua teste','ciudad teste','teste','teste',2226474.80,14.70,0.00,2226474.80,'pendente',1,NULL,'2025-07-12 00:30:24','2025-07-12 00:30:24',NULL,NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `wholesale_price` decimal(10,2) DEFAULT NULL,
  `retail_price` decimal(10,2) DEFAULT NULL,
  `min_wholesale_quantity` int(11) DEFAULT NULL,
  `unit_weight` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_type` enum('kg','unit') NOT NULL DEFAULT 'kg',
  `unit_display_name` varchar(20) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `promotion` tinyint(1) DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `show_price` tinyint(1) DEFAULT 1 COMMENT 'Se 1 mostra pre├ºo, se 0 mostra consultar vendedor',
  `has_min_quantity` tinyint(1) DEFAULT 1 COMMENT 'Se 1 tem quantidade m├¡nima, se 0 n├úo tem',
  `parent_product_id` int(11) DEFAULT NULL COMMENT 'ID do produto pai (para varia├º├Áes)',
  `variation_display` varchar(100) DEFAULT NULL COMMENT 'Nome da varia├º├úo (ex: 200ml, 500ml, 1L)',
  `variation_type` enum('size','flavor','color','other') DEFAULT NULL COMMENT 'Tipo de varia├º├úo',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `idx_products_show_price` (`show_price`),
  KEY `idx_products_has_min_quantity` (`has_min_quantity`),
  KEY `idx_parent_product_id` (`parent_product_id`),
  KEY `idx_variation_type` (`variation_type`),
  CONSTRAINT `fk_parent_product` FOREIGN KEY (`parent_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=172 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Anis Estrelado','Anis Estrelado.',50.00,60.00,10,1.00,'kg','kg',1900,'uploads/produtos/product_6830cd33dd24e.jpg',1,0,1,22,'2025-05-20 01:04:01','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(2,'canela casca 6cm','Canela casca 6cm',60.00,65.00,10,1.00,'kg','kg',320,'uploads/produtos/product_6830cce67a3ba.jfif',0,0,1,22,'2025-05-20 13:35:43','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(3,'AnÔö£┬ís','erva doce',28.00,70.00,25,1.00,'kg','kg',520,'uploads/produtos/product_6830cd8e3d378.png',1,0,1,16,'2025-05-20 17:12:24','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(4,'Nozes Mariposa Extralight Granel','Nozes',63.00,68.00,10,1.00,'kg','kg',900,'uploads/produtos/product_6830ccab19bad.jfif',0,0,1,31,'2025-05-20 22:41:03','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(5,'Flor de Hibisco','Hibisco flor',28.00,45.00,25,1.00,'kg','kg',616,'uploads/produtos/product_6830d21e30f27.jpg',0,0,1,16,'2025-05-20 22:42:12','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(6,'Amendoas S/C','Amendoas s/c',74.00,140.00,10,1.00,'kg','kg',194,'uploads/produtos/product_6830d2751879f.png',0,0,1,31,'2025-05-20 22:44:44','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(7,'ChÔö£├¡ Verde','',31.00,80.00,5,1.00,'kg','kg',1000,'uploads/produtos/product_682d0609a1b5d.jpg',0,0,1,16,'2025-05-20 22:45:29','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(8,'Nuez de Brasil S/c Mediana','Nuez de Brasil S/c Mediana',158.00,300.00,20,1.00,'kg','kg',1000,'uploads/produtos/product_6830d3ed03487.jfif',0,0,1,31,'2025-05-20 22:46:09','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(11,'Manzanilla','manzanilla',34.00,37.00,15,1.00,'kg','kg',380,'uploads/produtos/product_6830d3828519d.png',0,0,1,16,'2025-05-22 17:00:10','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(12,'Romero','romero',13.00,50.00,25,1.00,'kg','kg',250,'uploads/produtos/product_6830d46862f66.jpg',0,0,1,22,'2025-05-22 17:04:47','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(13,'Eneldo','eneldo',21.00,23.00,25,1.00,'kg','kg',300,'uploads/produtos/product_6830d50cbc314.png',0,0,1,22,'2025-05-22 17:08:45','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(14,'Boldo Chileno','Boldo Chileno',37.00,100.00,5,1.00,'kg','kg',100,'uploads/produtos/product_6830d554a7a33.jpg',0,0,1,16,'2025-05-22 17:18:20','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(15,'Flor de calÔö£┬«ndula','calendula flor',25.04,27.00,50,1.00,'kg','kg',320,'uploads/produtos/product_682f5f463f9ff.jpeg',0,0,1,16,'2025-05-22 17:30:46','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(16,'hinojo','Hinojo',12.00,13.00,25,1.00,'kg','kg',175,'uploads/produtos/product_682f6d88447aa.jpg',0,0,1,16,'2025-05-22 18:27:37','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(17,'Aceite de coco virgen extra copra 200 ml','Aceite de coco virgen extra copra 200 ml',22.00,30.00,6,0.20,'unit','unidades',36,'uploads/produtos/product_682f723951b09.png',0,0,1,33,'2025-05-22 18:36:47','2025-07-11 20:59:22',1,0,NULL,NULL,NULL),(18,'Aceite de coco virgen extra copra 500 ml','Aceite de coco virgen extra copra 500 ml',22.00,70.00,6,0.50,'unit','unidades',36,'0',0,0,1,33,'2025-05-22 18:43:44','2025-07-11 20:59:22',1,0,NULL,NULL,NULL),(19,'Clavo de olor','Clavo de olor',70.00,76.00,10,1.00,'kg','kg',247,'uploads/produtos/product_682f7671c7a71.jfif',0,0,1,16,'2025-05-22 19:09:37','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(20,'Lavanda','Lavanda',85.00,300.00,5,1.00,'kg','kg',1119,'uploads/produtos/product_682f7846064f1.png',0,0,1,22,'2025-05-22 19:14:01','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(21,'Bicarbonato','Bicarbonato',8.00,12.00,25,1.00,'kg','kg',100,'uploads/produtos/product_682f79592f352.jpg',0,0,1,22,'2025-05-22 19:20:15','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(22,'CÔö£Ôòærcuma','curcuma',19.00,21.00,10,1.00,'kg','kg',390,'uploads/produtos/product_682f7c4100ecb.jpg',0,0,1,22,'2025-05-22 19:34:25','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(23,'Pimienta negra en polvo','Pimienta negra en polvo',13.00,50.00,10,1.00,'kg','kg',390,'uploads/produtos/product_682f7d3fce970.jpg',0,0,1,22,'2025-05-22 19:38:01','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(24,'Fungui Seco Peruano','hongo Seco',56.00,45.00,10,1.00,'kg','kg',100,'uploads/produtos/product_682f7e600ffa8.jpg',0,0,1,22,'2025-05-22 19:43:28','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(25,'Linaza Dorada','Linaza Dorada',12.00,30.00,25,1.00,'kg','kg',100,'uploads/produtos/product_682f7f7989f01.jpg',0,0,1,18,'2025-05-22 19:48:09','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(26,'Linaza marrÔö£Ôöén','Linaza marrÔö£Ôöén',9.00,20.00,25,1.00,'kg','kg',100,'uploads/produtos/product_682f80c050562.jpg',0,0,1,18,'2025-05-22 19:53:36','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(27,'ChÔö£┬ía','ChÔö£┬ía',22.00,50.00,25,1.00,'kg','kg',100,'uploads/produtos/product_682f81635ee7f.jpg',0,0,1,18,'2025-05-22 19:55:50','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(28,'Maca Peruana','Maca Peruana',33.00,70.00,10,1.00,'kg','kg',20,'uploads/produtos/product_682f82a3721ba.jpg',0,0,1,17,'2025-05-22 20:01:39','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(29,'Espirulina Granel','Espirulina Granel',43.00,125.00,10,1.00,'kg','kg',100,'uploads/produtos/product_682f83391191d.jpg',0,0,1,17,'2025-05-22 20:04:09','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(30,'Pistachos IranÔö£┬íes','Pistachos IranÔö£┬íes',120.00,200.00,10,1.00,'kg','kg',100,'uploads/produtos/product_682f85183e73f.png',0,0,1,20,'2025-05-22 20:12:08','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(31,'cacao alcalino en polvo','cacao alcalino en polvo',18.00,50.00,25,1.00,'kg','kg',346,'uploads/produtos/product_682f87234371e.jpg',0,0,1,32,'2025-05-22 20:20:51','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(32,'Xilitol Cristal','Xilitol Cristal',36.00,80.00,25,1.00,'kg','kg',100,'uploads/produtos/product_682f886a04c79.jfif',0,0,1,27,'2025-05-22 20:26:18','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(33,'Stevia','Stevia',92.00,120.00,10,1.00,'kg','kg',100,'uploads/produtos/product_682f89410c520.jpg',0,0,1,27,'2025-05-22 20:29:53','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(34,'azÔö£Ôòæcar de coco','azÔö£Ôòæcar de coco',33.00,36.00,10,1.00,'kg','kg',310,'uploads/produtos/product_682f89cfcdf88.jpg',0,0,1,27,'2025-05-22 20:32:15','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(35,'Amaranto','Amaranto',38.00,65.00,25,1.00,'kg','kg',100,'uploads/produtos/product_682f8b8cb1d2f.jpg',0,0,1,34,'2025-05-22 20:39:40','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(36,'avena hojuelas finas','avena hojuelas finas',8.00,15.00,25,1.00,'kg','kg',50,'uploads/produtos/product_682f8c2e90440.jpg',0,0,1,34,'2025-05-22 20:42:22','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(37,'avena hojuelas grueso','avena hojuelas grueso',8.00,15.00,25,1.00,'kg','kg',200,'uploads/produtos/product_682f8cc5aa8e7.jpg',0,0,1,34,'2025-05-22 20:44:53','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(38,'harina de trigo sarraceno','harina de trigo sarraceno',20.00,40.00,10,1.00,'kg','kg',100,'uploads/produtos/product_682f8e874e031.jpg',0,0,1,19,'2025-05-22 20:52:23','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(39,'Psyllium','Psyllium',31.00,100.00,25,1.00,'kg','kg',174,'uploads/produtos/product_682f8f2be20a3.jpg',0,0,1,17,'2025-05-22 20:55:07','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(40,'semilla de girasol s/casca','semilla de girasol s/casca',18.00,30.00,25,1.00,'kg','kg',100,'uploads/produtos/product_682f8fff680be.jpg',0,0,1,18,'2025-05-22 20:58:39','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(41,'pimentÔö£Ôöén doce','pimentÔö£Ôöén doce',12.00,30.00,10,1.00,'kg','kg',55,'uploads/produtos/product_682f909524238.jpg',0,0,1,22,'2025-05-22 21:01:09','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(42,'Canela en polvo 100% pura','Canela en polvo 100% pura',14.00,16.00,10,1.00,'kg','kg',700,'uploads/produtos/product_682f9129df770.jpg',0,0,1,22,'2025-05-22 21:03:37','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(43,'PERPETUA - SIEMPRE VIVE A GRANEL','PERPETUA - SIEMPRE VIVE A GRANEL',85.00,200.00,15,1.00,'kg','kg',100,'uploads/produtos/product_682f934c55859.jpg',0,0,1,16,'2025-05-22 21:12:44','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(44,'Eritritol','Eritritol',24.00,26.00,25,1.00,'kg','kg',99,'uploads/produtos/product_6830ae3340cc7.jpg',0,0,1,27,'2025-05-23 17:15:06','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(45,'Granola sin azÔö£Ôòæcar','granola sin azÔö£Ôòæcar',31.00,50.00,10,1.00,'kg','kg',40,'uploads/produtos/product_6830b057c677b.jpg',0,0,1,34,'2025-05-23 17:28:55','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(46,'Granola Tradicional','Granola Tradicional',19.00,21.00,10,1.00,'kg','kg',160,'uploads/produtos/product_6830b17ce5083.jpg',0,0,1,34,'2025-05-23 17:33:48','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(47,'Ginseng','Ginseng',18.00,50.00,10,1.00,'kg','kg',100,'uploads/produtos/product_6830b6a7f168d.jpg',0,0,1,17,'2025-05-23 17:55:51','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(48,'Harina de centeno fina','harina de centeno fina',7.00,10.00,25,1.00,'kg','kg',100,'uploads/produtos/product_6830b745a63c8.jpg',0,0,1,19,'2025-05-23 17:58:29','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(49,'Semilla de calabaza cruda','semilla de calabaza cruda',41.50,45.00,25,1.00,'kg','kg',570,'uploads/produtos/product_6830b8197b885.jfif',0,0,1,18,'2025-05-23 18:02:01','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(50,'harina integral','harina integral',7.00,12.00,25,1.00,'kg','kg',100,'uploads/produtos/product_6830b916af15d.jfif',0,0,1,19,'2025-05-23 18:06:14','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(51,'Leche de coco en polvo','leche de coco en polvo',43.00,75.00,15,1.00,'kg','kg',56,'uploads/produtos/product_6830bbd0ba90d.jpg',0,0,1,20,'2025-05-23 18:17:52','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(52,'Polvo de remolacha','polvo de remolacha',32.00,90.00,10,1.00,'kg','kg',100,'uploads/produtos/product_6830bdba2b289.jfif',0,0,1,22,'2025-05-23 18:26:02','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(53,'OrÔö£┬«gano','OrÔö£┬«gano',28.00,60.00,13,1.00,'kg','kg',250,'uploads/produtos/product_6830c1bd1c812.jpeg',0,0,1,22,'2025-05-23 18:43:09','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(54,'TÔö£┬« de diente de leÔö£Ôöén','tÔö£┬« de diente de leÔö£Ôöén',40.00,80.00,5,1.00,'kg','kg',250,'uploads/produtos/product_6830c3b0ddf4b.jpeg',0,0,1,16,'2025-05-23 18:51:28','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(55,'Cranberry','Cranberry',46.00,100.00,10,1.00,'kg','kg',100,'uploads/produtos/product_6830c5bfc7e72.jpg',0,0,1,20,'2025-05-23 19:00:15','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(56,'Cilantro en polvo','cilantro en polvo',12.00,25.00,10,1.00,'kg','kg',10,'uploads/produtos/product_6830c791c9903.png',0,0,1,22,'2025-05-23 19:08:01','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(57,'Comino en polvo','comino en polvo',12.00,40.00,10,1.00,'kg','kg',100,'uploads/produtos/product_6830c82172d68.jpg',0,0,1,22,'2025-05-23 19:10:25','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(58,'Comino en grano','Comino en grano',27.00,30.00,10,1.00,'kg','kg',100,'uploads/produtos/product_6830c88e845de.jpg',0,0,1,18,'2025-05-23 19:12:14','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(59,'Albahaca en polvo','albahaca en polvo',10.00,20.00,10,1.00,'kg','kg',100,'uploads/produtos/product_6830cc4203940.png',0,0,1,22,'2025-05-23 19:28:02','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(60,'Harina de uva','harina de uva',28.00,31.00,10,1.00,'kg','kg',20,'uploads/produtos/product_6830d69b880e3.jpg',0,0,1,19,'2025-05-23 20:12:11','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(61,'Polvo de guaranÔö£├¡','polvo de guaranÔö£├¡',29.00,100.00,10,1.00,'kg','kg',30,'uploads/produtos/product_6830d72db2587.png',0,0,1,17,'2025-05-23 20:14:37','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(62,'Catuaba en polvo','catuaba en polvo',17.00,18.00,10,1.00,'kg','kg',50,'uploads/produtos/product_6830d7b80cf4c.png',0,0,1,17,'2025-05-23 20:16:56','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(63,'Jengibre en polvo','jengibre en polvo',33.00,36.00,10,1.00,'kg','kg',298,'uploads/produtos/product_6830d8950215d.jpg',0,0,1,22,'2025-05-23 20:20:37','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(64,'Cacao en polvo alcalino negro','cacao en polvo alcalino negro',48.00,90.00,10,1.00,'kg','kg',47,'uploads/produtos/product_6830d9485428b.jpg',0,0,1,32,'2025-05-23 20:23:36','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(65,'PimentÔö£Ôöén picante','pimentÔö£Ôöén picante',13.00,25.00,10,1.00,'kg','kg',187,'uploads/produtos/product_6830da855371b.jpg',0,0,1,22,'2025-05-23 20:28:53','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(66,'PimentÔö£Ôöén desgomado','pimentÔö£Ôöén desgomado',15.00,17.00,10,1.00,'kg','kg',340,'uploads/produtos/product_6830daf9cf471.jfif',0,0,1,22,'2025-05-23 20:30:49','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(67,'Gikgo biloba en po','gikgo biloba en po',100.00,120.00,10,1.00,'kg','kg',100,'uploads/produtos/product_6830dbca142dd.png',0,0,1,17,'2025-05-23 20:34:18','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(68,'Hojuelas de menta','hojuelas de menta',40.00,45.00,10,1.00,'kg','kg',100,'uploads/produtos/product_6830dcc31b3a8.jpg',0,0,1,16,'2025-05-23 20:38:27','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(69,'AcaÔö£┬í en polvo','acaÔö£┬í en polvo',130.00,220.00,5,1.00,'kg','kg',100,'uploads/produtos/product_6834b7b02bf37.png',0,0,1,17,'2025-05-26 18:49:20','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(70,'Hojuelas de pimienta de Calabria','Hojuelas de pimienta de Calabria',24.00,45.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683600ccd7c7b.png',0,0,1,22,'2025-05-27 18:13:32','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(71,'Pimienta calabresa en polvo','pimienta calabresa en polvo',14.00,15.00,10,1.00,'kg','kg',42,'uploads/produtos/product_683601ae096b2.jfif',0,0,1,22,'2025-05-27 18:17:18','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(72,'Macadamia','Macadamia',200.00,300.00,5,1.00,'kg','kg',100,'uploads/produtos/product_68360f6988056.jpg',0,0,1,31,'2025-05-27 19:15:53','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(73,'Matcha JaponÔö£┬«s Soluble','Matcha JaponÔö£┬«s Soluble',182.00,300.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683611158412b.png',0,0,1,16,'2025-05-27 19:23:01','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(74,'TÔö£┬«  cangarosa a granel','te cangarosa a granel',50.00,80.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68361be89d009.jpeg',0,0,1,16,'2025-05-27 19:25:27','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(75,'Palo Tienente','PALO Tienente',37.00,80.00,5,1.00,'kg','kg',100,'uploads/produtos/product_683613db09486.jfif',0,0,1,22,'2025-05-27 19:34:51','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(76,'Cascara de Naranja','Cascara de Naranja',17.00,20.00,14,1.00,'kg','kg',100,'uploads/produtos/product_6840af58d3e33.jpg',0,0,1,22,'2025-05-27 19:38:25','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(77,'Vinagre OrgÔö£├│nico de 4 EstaÔö£┬║Ôö£├ües 500ML','VINAGRE ORGÔö£├╝NICO DE 4 EstaÔö£┬║Ôö£├ües 500ML',22.00,24.00,10,0.50,'unit','unidades',100,'uploads/produtos/product_683615a26cc1f.png',0,0,1,33,'2025-05-27 19:42:26','2025-07-11 20:59:22',1,0,NULL,NULL,NULL),(78,'Condimento Pega Marido','Condimento Pega Marido',32.00,60.00,10,1.00,'kg','kg',100,'uploads/produtos/product_6836162b32126.jpg',0,0,1,22,'2025-05-27 19:44:43','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(79,'Marapuama en polvo','Marapuama en polvo',20.00,30.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683617d3d1e64.png',0,0,1,17,'2025-05-27 19:51:47','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(80,'Harina de Almendra Regal','Harina de Almendra Regal',76.00,92.00,10,1.00,'kg','kg',100,'uploads/produtos/product_6836188a051e7.png',0,0,1,19,'2025-05-27 19:54:50','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(81,'Chlorella en Polvo','Chlorella en Polvo',138.00,200.00,5,1.00,'kg','kg',100,'uploads/produtos/product_683619e876431.jpg',0,0,1,17,'2025-05-27 20:00:40','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(82,'FÔö£┬«cula de Patata','FÔö£┬«cula de Patata',11.00,20.00,25,1.00,'kg','kg',100,'uploads/produtos/product_68361a7aa30ac.png',0,0,1,19,'2025-05-27 20:03:06','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(83,'Condimento tÔö£├¡rtaro a granel','condimento tÔö£├¡rtaro a granel',30.00,80.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68361c595469b.png',0,0,1,22,'2025-05-27 20:11:05','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(84,'Harina de Arroz','Harina de Arroz',8.00,15.00,25,1.00,'kg','kg',100,'uploads/produtos/product_68361cbc14123.png',0,0,1,19,'2025-05-27 20:12:44','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(85,'Ginseng de raÔö£┬íz rayada','ginseng de raÔö£┬íz rayada',100.00,190.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68361d2ddb80b.jpg',0,0,1,17,'2025-05-27 20:14:37','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(86,'Enebro de grano','enebro de grano',34.00,100.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68361daa36628.jfif',0,0,1,18,'2025-05-27 20:16:42','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(87,'Cha garra del diablo','cha garra del diablo',150.00,176.06,5,1.00,'kg','kg',100,'uploads/produtos/product_68361e2fa76e7.png',0,0,1,16,'2025-05-27 20:18:55','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(88,'Kiwi Deshidratado Importado','Kiwi Deshidratado Importado',53.00,100.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68361e9b94e6f.jfif',0,0,1,20,'2025-05-27 20:20:43','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(89,'Naranja Deshidratada Redonda Tradicional','NARANJA DESHIDRATADA REDONDA TRADICIONAL',60.00,120.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68361ef08dd53.jfif',0,0,1,20,'2025-05-27 20:22:08','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(90,'RaÔö£┬íz de ortiga arrancada','RaÔö£┬íz de ortiga arrancada',80.00,80.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68361f578d226.png',0,0,1,17,'2025-05-27 20:23:51','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(91,'Sal Rosa Fina del Himalayo','Sal Rosa Fina del Himalayo',5.00,5.63,10,1.00,'kg','kg',100,'uploads/produtos/product_68361fddebb00.jfif',0,0,1,22,'2025-05-27 20:26:05','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(92,'Sal Rosa Gruessa del Himalayo','Sal Rosa Gruessa del Himalayo',12.00,12.19,10,1.00,'kg','kg',100,'uploads/produtos/product_683620296a1d7.jfif',0,0,1,22,'2025-05-27 20:27:21','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(93,'Hoja de laurel','Hoja de laurel',49.00,54.00,50,1.00,'kg','kg',100,'uploads/produtos/product_6836207e08122.jfif',0,0,1,22,'2025-05-27 20:28:46','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(94,'Gleba mediana pasa negra','gleba mediana pasa negra',19.00,21.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683620d7e8ea3.jfif',0,0,1,20,'2025-05-27 20:30:15','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(95,'Ajo granulado','ajo granulado',37.00,100.00,25,1.00,'kg','kg',100,'uploads/produtos/product_68362118bdd32.jfif',0,0,1,22,'2025-05-27 20:31:20','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(96,'Caldo de carne','caldo de carne',4.00,5.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683621c253066.png',0,0,1,22,'2025-05-27 20:34:10','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(97,'Caldo de pollo','caldo de pollo',7.00,35.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68362219838e2.png',0,0,1,22,'2025-05-27 20:35:37','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(98,'Lenteja roja canadiense','lenteja roja canadiense',17.00,35.00,45,1.00,'kg','kg',100,'uploads/produtos/product_6836227a641bb.jfif',0,0,1,18,'2025-05-27 20:37:14','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(99,'TÔö£┬« de bÔö£├¡lsamo de limÔö£Ôöén','tÔö£┬« de bÔö£├¡lsamo de limÔö£Ôöén',32.00,34.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683622fa44b88.jpg',0,0,1,16,'2025-05-27 20:39:22','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(100,'Matcha desintoxicante a granel','matcha desintoxicante a granel',103.00,200.00,5,1.00,'kg','kg',100,'uploads/produtos/product_6836235c67cf1.jfif',0,0,1,16,'2025-05-27 20:41:00','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(101,'Nuez pecana entera','nuez pecana entera',130.00,200.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683623c7891bc.jpg',0,0,1,31,'2025-05-27 20:42:47','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(102,'TÔö£┬« de morera blanca','tÔö£┬« de morera blanca',21.00,70.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683624241e187.png',0,0,1,16,'2025-05-27 20:44:20','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(103,'Gelatina en polvo sin sabor','gelatina en polvo sin sabor',61.00,200.00,25,1.00,'kg','kg',100,'uploads/produtos/product_683626fe9a488.jfif',0,0,1,27,'2025-05-27 20:56:30','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(104,'Condimento Ana Maria','Condimento Ana Maria',22.00,50.00,10,1.00,'kg','kg',100,'uploads/produtos/product_6836277f7a5e1.png',0,0,1,22,'2025-05-27 20:58:39','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(105,'Condimento Edu','Condimento Edu',26.00,50.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683627c946ef6.png',0,0,1,22,'2025-05-27 20:59:53','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(106,'Tomillo','Tomillo',60.00,60.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683628415e2f3.png',0,0,1,22,'2025-05-27 21:01:53','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(107,'Harina de Coco','harina de coco',16.00,35.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683628b803b5c.png',0,0,1,19,'2025-05-27 21:03:52','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(108,'Harina de Almendra Parmex','harina de almendra Parmex',44.00,100.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683628f156281.png',0,0,1,19,'2025-05-27 21:04:49','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(109,'Damasco Turco Dulce','Damasco Turco Dulce',58.00,65.00,12,1.00,'kg','kg',100,'uploads/produtos/product_6836296413d5c.jfif',0,0,1,20,'2025-05-27 21:06:44','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(110,'Raiz de Unha de Gato rallada','RAÔö£├¼Z DE UÔö£├ªA DE GATO RALLADA',85.00,85.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68362b192d42f.jpg',0,0,1,17,'2025-05-27 21:14:01','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(111,'Cacauzinho 70% cacao','Cacauzinho 70% cacao',183.00,200.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683757559689d.jpg',0,0,1,28,'2025-05-28 18:35:01','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(112,'70% chispas de chocolate amargo','70% chispas de chocolate amargo',183.00,200.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683758d3a6ddd.png',0,0,1,28,'2025-05-28 18:41:23','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(113,'DÔö£├¡til de cacao 70%','DÔö£├¡til de cacao 70%',120.00,200.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68375a4d21b2f.png',0,0,1,28,'2025-05-28 18:47:41','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(114,'ArÔö£├¡ndano','arÔö£├¡ndano',146.00,180.00,11,1.00,'kg','kg',1012,'uploads/produtos/product_68375bd9cb12d.png',0,0,1,20,'2025-05-28 18:54:17','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(115,'Aceite de coco virgen extra-coco show 500 ML','Aceite de coco virgen extra-coco show 500 ML',38.00,42.00,6,0.50,'unit','unidades',54,'0',0,0,1,33,'2025-05-28 19:01:43','2025-07-11 20:59:22',1,0,NULL,NULL,NULL),(116,'Aceite de coco extra virgen show 200ML','ACEITE DE COCO EXTRA VIRGEN SHOW 200ML',18.00,25.00,6,0.20,'unit','unidades',72,'uploads/produtos/product_68375e38b26a2.png',0,0,1,33,'2025-05-28 19:04:24','2025-07-11 20:59:22',1,0,NULL,NULL,NULL),(117,'Aceite de coco virgen extra con 1L Santo Ôö£├┤leo','OLEO DE COCO EXTRAVIRGEM DE PELICULA 1L SANTO OLEO',61.00,NULL,6,1.00,'kg','unidade',30,'0',0,0,1,33,'2025-05-28 19:08:53','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(118,'Cubo aceite de coco virgen extra 3,2 Santo Ôö£├┤leo','CUBO ACEITE DE COCO VIRGEN EXTRA 3,2L SANTO OLEO',220.00,265.00,6,1.00,'kg','kg',30,'uploads/produtos/product_683760e4e7fea.png',0,0,1,33,'2025-05-28 19:15:48','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(119,'Avellana sin cascara','Avellana sin cascara',99.00,200.00,10,1.00,'kg','kg',50,'uploads/produtos/product_683763709ef67.jpg',0,0,1,31,'2025-05-28 19:26:40','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(120,'Arroz negro','arroz negro',33.00,5.00,25,1.00,'kg','kg',100,'uploads/produtos/product_683764955d4cf.jfif',0,0,1,18,'2025-05-28 19:31:33','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(121,'Coco show aceite de coco virgen extra  70 ML','Coco show aceite de coco virgen extra  70 ML',12.00,15.49,10,0.07,'unit','unidades',30,'0',0,0,1,33,'2025-05-28 19:36:29','2025-07-11 20:59:22',1,0,NULL,NULL,NULL),(122,'Anacardo crudo a granel','Anacardo crudo a granel',65.00,130.00,25,1.00,'kg','kg',100,'uploads/produtos/product_6837669a61198.jfif',0,0,1,31,'2025-05-28 19:40:10','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(123,'Tomate deshidratado granulado','Tomate deshidratado granulado KG',57.89,70.00,10,1.00,'kg','kg',50,'uploads/produtos/product_68376d827ea6d.png',0,0,1,20,'2025-05-28 20:09:38','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(124,'Choco Ball','Choco ball',22.00,40.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68376ef6a29be.jpg',0,0,1,34,'2025-05-28 20:15:50','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(125,'Harina de linaza dorada','harina de linaza dorada',16.00,18.00,10,1.00,'kg','kg',180,'uploads/produtos/product_68377c966ad25.jfif',0,0,1,19,'2025-05-28 21:13:58','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(126,'harina de linaza marron','harina de linaza marron',18.00,20.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68377d202d1bc.png',0,0,1,19,'2025-05-28 21:16:16','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(127,'Harina de avena','Harina de avena',9.00,10.00,10,1.00,'kg','kg',100,'uploads/produtos/product_68377de501ca4.png',0,0,1,19,'2025-05-28 21:19:33','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(128,'Agar Agar en polvo','Agar Agar en polvo',90.00,97.00,10,1.00,'kg','kg',10,'uploads/produtos/product_68389c1497265.png',0,0,1,27,'2025-05-29 17:40:36','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(129,'Cardamomo en Polvo','Cardamomo en polvo',266.00,300.00,10,1.00,'kg','kg',10,'uploads/produtos/product_68389cfaca065.png',0,0,1,22,'2025-05-29 17:44:26','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(130,'Cardamomo en Grano','Cardamomo en Grano',385.00,700.00,10,1.00,'kg','kg',30,'uploads/produtos/product_68389da1d9a15.jfif',0,0,1,22,'2025-05-29 17:47:13','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(131,'Goji Berry','Goji Berry',49.00,53.00,10,1.00,'kg','kg',30,'uploads/produtos/product_6838a3c987816.jpg',0,0,1,20,'2025-05-29 18:13:29','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(132,'Garbanzos 9mm','Garbanzos 9mm',12.00,13.00,25,1.00,'kg','kg',2755,'uploads/produtos/product_6838a4dcaf5a7.jfif',0,0,1,18,'2025-05-29 18:18:04','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(133,'Germen de trigo','Germen de trigo',19.00,35.00,10,1.00,'kg','kg',100,'uploads/produtos/product_6838a81a54147.png',0,0,1,34,'2025-05-29 18:31:54','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(134,'Alfafa','Alfafa',39.00,43.94,10,1.00,'kg','kg',100,'uploads/produtos/product_6838aa031b8bd.png',0,0,1,16,'2025-05-29 18:40:03','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(135,'Ciruela sin Hueso','Ciruela sin Hueso',35.00,38.00,10,1.00,'kg','kg',95,'uploads/produtos/product_6838ac038bd82.jpeg',0,0,1,20,'2025-05-29 18:48:35','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(136,'Colageno Hidrolizado','Colageno Hidrolizado',51.00,150.00,10,1.00,'kg','kg',85,'uploads/produtos/product_6838ad0c422f3.png',0,0,1,17,'2025-05-29 18:53:00','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(137,'Quinua negra en granos','Quinua negra en granos',31.00,50.00,25,1.00,'kg','kg',100,'uploads/produtos/product_6838b2c533869.jpg',0,0,1,34,'2025-05-29 19:17:25','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(138,'Quinua blanca en grano','Quinua blanca en grano',34.00,50.00,25,1.00,'kg','kg',100,'uploads/produtos/product_6838b36fb8fc4.png',0,0,1,34,'2025-05-29 19:20:15','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(139,'Frijol Fradinho','Frijol Fradinho',11.00,20.00,25,1.00,'kg','kg',100,'uploads/produtos/product_6838b4d0bf842.png',0,0,1,34,'2025-05-29 19:26:08','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(141,'Nueces de brasil en trozos','nueces de brasil en trozos',65.00,130.00,20,1.00,'kg','kg',100,'uploads/produtos/product_6838b669804c5.jfif',0,0,1,31,'2025-05-29 19:32:57','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(142,'Maltitol','Maltitol',25.00,40.00,25,1.00,'kg','kg',1000,'uploads/produtos/product_6838b71fa6840.png',0,0,1,27,'2025-05-29 19:35:59','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(143,'AzÔö£Ôòæcar moreno','azÔö£Ôòæcar moreno',11.00,20.00,10,1.00,'kg','kg',1000,'uploads/produtos/product_6838b7bf26f78.jpg',0,0,1,27,'2025-05-29 19:38:39','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(144,'AzÔö£Ôòæcar de vainilla','azÔö£Ôòæcar de vainilla',11.00,22.00,10,1.00,'kg','kg',110,'uploads/produtos/product_6838b8ad1468e.jfif',0,0,1,27,'2025-05-29 19:42:37','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(145,'Nueces de cuarzo','nueces de cuarzo',52.00,56.00,10,1.00,'kg','kg',1000,'uploads/produtos/product_6838bc3c85fe6.jfif',0,0,1,31,'2025-05-29 19:57:48','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(146,'Aceite de coco virgen extra  santo oleo 500ML','aceite de coco virgen extra aceite santo 500ML',42.00,45.00,6,0.50,'unit','unidades',1000,'uploads/produtos/product_6838c3300d3b7.png',0,0,1,33,'2025-05-29 20:27:28','2025-07-11 20:59:22',1,0,117,'500ml','size'),(147,'aceite de coco virgen extra aceite santo 400ML','aceite de coco virgen extra aceite santo 400ML',27.00,45.00,6,0.40,'unit','unidades',1000,'uploads/produtos/product_6838c3c5d6f75.png',0,0,1,33,'2025-05-29 20:29:57','2025-07-11 20:59:22',1,0,117,'400ml','size'),(148,'aceite de coco virgen extra aceite santo 200ML','aceite de coco virgen extra aceite santo 200ML',20.00,22.00,6,0.20,'unit','unidades',1001,'uploads/produtos/product_6838c455b1769.png',0,0,1,33,'2025-05-29 20:32:21','2025-07-11 20:59:22',1,0,117,'200ml','size'),(149,'aceite de coco virgen extra aceite santo 100ML','aceite de coco virgen extra aceite santo 100ML',8.00,10.00,6,0.10,'unit','unidades',10100,'uploads/produtos/product_6838c4d5de95f.png',0,0,1,33,'2025-05-29 20:34:29','2025-07-11 20:59:22',1,0,117,'100ml','size'),(150,'aceite de coco sin sabor 3.2L Santo Ôö£├┤leo','aceite de coco sin sabor 3.2L Santo Ôö£├┤leo',219.00,260.00,6,1.00,'kg','kg',1000,'uploads/produtos/product_6838c5980b177.png',0,0,1,33,'2025-05-29 20:37:44','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(151,'aceite de coco sin sabor 1L Santo Ôö£├┤leo','aceite de coco sin sabor 1L Santo Ôö£├┤leo',69.00,72.00,6,1.00,'kg','kg',1010,'uploads/produtos/product_6838c6116c5b4.png',0,0,1,33,'2025-05-29 20:39:45','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(152,'Frispy mezcla original a granel','Frispy mezcla original a granel',130.00,140.00,10,1.00,'kg','kg',100,'uploads/produtos/product_683a02f722af6.jpg',0,0,1,20,'2025-05-30 19:11:51','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(153,'Cereza seca','cereza seca',69.00,74.00,12,1.00,'kg','kg',100,'uploads/produtos/product_683df3cd1167e.jfif',0,0,1,20,'2025-06-02 18:56:13','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(154,'Condimento para carne','condimento para carne',35.00,40.00,10,1.00,'kg','kg',1451,'uploads/produtos/product_683df8d2bdfb5.jpg',0,0,1,22,'2025-06-02 19:17:38','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(155,'Condimento para pollo','condimento para pollo',35.00,40.00,10,1.00,'kg','kg',2008,'uploads/produtos/product_6841fc6d1694c.jpeg',0,0,1,22,'2025-06-02 19:21:01','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(156,'Condimento para pescado','condimento para pescado',35.00,40.00,10,1.00,'kg','kg',4875,'uploads/produtos/product_683dfa56b9798.jpg',0,0,1,22,'2025-06-02 19:24:06','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(157,'Hojuelas de perejil seco','hojuelas de perejil seco',10.00,17.00,10,1.00,'kg','kg',1001,'uploads/produtos/product_6841de992d781.jpg',0,0,1,22,'2025-06-05 18:14:49','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(158,'Cebada en grano','cebada en grano',8.00,14.00,10,1.00,'kg','kg',1452,'uploads/produtos/product_6841e0cd59643.png',0,0,1,34,'2025-06-05 18:24:13','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(159,'Harina panko','Harina panko',19.00,26.00,10,1.00,'kg','kg',1478526,'uploads/produtos/product_6841e1d86958e.png',0,0,1,19,'2025-06-05 18:28:40','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(160,'Clavo de olor en polvo','Clavo de olor en polvo',50.00,60.00,10,1.00,'kg','kg',14785,'uploads/produtos/product_6841fb51b81c9.png',0,0,1,22,'2025-06-05 20:17:21','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(161,'Cebolla, perejil y ajo','cebolla, perejil y ajo',34.00,40.00,10,1.00,'kg','kg',1412,'uploads/produtos/product_684203d811632.png',0,0,1,22,'2025-06-05 20:53:44','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(162,'Manzanilla Egipcia','Manzanilla Egipcia',39.00,45.00,10,1.00,'kg','kg',11115,'uploads/produtos/product_6842074ccf45f.png',0,0,1,22,'2025-06-05 21:08:28','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(163,'Tribulus terrestris en polvo','tribulus terrestris en polvo',37.00,45.00,10,1.00,'kg','kg',12025,'uploads/produtos/product_6843294faa018.jpg',0,0,1,17,'2025-06-06 17:45:51','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(164,'Lemon Pepper','Lemon Pepper',20.00,25.00,10,1.00,'kg','kg',145287,'uploads/produtos/product_684329f6f2949.png',0,0,1,22,'2025-06-06 17:48:38','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(165,'flor de jazmÔö£┬ín de tÔö£┬«','flor de jazmÔö£┬ín de tÔö£┬«',300.00,310.00,5,1.00,'kg','kg',784512,'uploads/produtos/product_68432b8f06990.jpg',0,0,1,16,'2025-06-06 17:53:55','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(166,'Almendras laminadas','almendras laminadas',89.00,95.00,10,1.00,'kg','kg',1424,'uploads/produtos/product_68432e9e53f63.png',0,0,1,31,'2025-06-06 18:08:30','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(167,'TÔö£┬« de carqueja','TÔö£┬« de carqueja',50.00,60.00,10,1.00,'kg','kg',142587,'uploads/produtos/product_68432f72001d9.png',0,0,1,16,'2025-06-06 18:12:02','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(168,'Hojuelas de coco rallado','hojuelas de coco rallado',60.00,65.00,10,1.00,'kg','kg',74258,'uploads/produtos/product_684335371f9a7.png',0,1,1,20,'2025-06-06 18:36:39','2025-07-11 20:45:22',1,0,NULL,NULL,NULL),(169,'Mani sim piel y sin sal','Mani sim piel y sin sal',20.00,NULL,10,1.00,'kg','kg',125,'0',1,0,1,31,'2025-06-06 19:02:23','2025-07-11 20:45:22',1,0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotional_popup`
--

DROP TABLE IF EXISTS `promotional_popup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotional_popup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `start_date` datetime DEFAULT current_timestamp(),
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotional_popup`
--

LOCK TABLES `promotional_popup` WRITE;
/*!40000 ALTER TABLE `promotional_popup` DISABLE KEYS */;
INSERT INTO `promotional_popup` VALUES (4,'Canela','assets/images/popup/popup_1751930568.png',1,'2025-07-07 20:22:48',NULL,'2025-07-07 23:22:48','2025-07-07 23:22:48');
/*!40000 ALTER TABLE `promotional_popup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rotating_banner`
--

DROP TABLE IF EXISTS `rotating_banner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rotating_banner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `background_color` varchar(10) DEFAULT '#27AE60',
  `text_color` varchar(10) DEFAULT '#FFFFFF',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rotating_banner`
--

LOCK TABLES `rotating_banner` WRITE;
/*!40000 ALTER TABLE `rotating_banner` DISABLE KEYS */;
INSERT INTO `rotating_banner` VALUES (1,'┬¡ãÆ├┤┬¬ ENTREGAMOS TU PEDIDO HASTA VOS! ┬¡ãÆ├£├£','#deaa1b','#ffffff',1,0,'2025-06-09 16:35:51','2025-06-09 19:29:26'),(2,'┬¡ãÆ├åÔöé LOS PAGOS SE REALIZAN VÔö£├¼A TRANSFERENCIA BANCARIA EN PARAGUAY ┬¡ãÆ├º├ü┬¡ãÆ├º┬Ñ','#3edb33','#ffffff',1,0,'2025-06-09 16:37:44','2025-06-09 19:08:42'),(3,'120 CLIENTES EN TODO PARAGUAY','#fff700','#ffffff',1,0,'2025-06-09 19:29:50','2025-07-07 23:13:16');
/*!40000 ALTER TABLE `rotating_banner` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_settings`
--

DROP TABLE IF EXISTS `store_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_name` varchar(255) NOT NULL,
  `store_description` text DEFAULT NULL,
  `whatsapp_number` varchar(50) DEFAULT NULL,
  `shipping_rate` decimal(10,2) NOT NULL DEFAULT 1500.00,
  `address` text DEFAULT NULL,
  `business_hours` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `social_facebook` varchar(255) DEFAULT NULL,
  `social_instagram` varchar(255) DEFAULT NULL,
  `social_twitter` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `contact_seller_text` varchar(255) DEFAULT 'Consultar con el vendedor' COMMENT 'Texto em espanhol para produtos sem pre├ºo',
  `default_min_quantity` int(11) DEFAULT 10 COMMENT 'Quantidade m├¡nima padr├úo para novos produtos',
  `hide_retail_price` tinyint(1) DEFAULT 0 COMMENT 'Se 1, remove pre├ºo de varejo do sistema',
  `enable_shipping` tinyint(1) DEFAULT 1 COMMENT 'Se 1 calcula frete, se 0 n├úo mostra frete',
  `shipping_control_text` varchar(255) DEFAULT 'Frete calculado automaticamente' COMMENT 'Texto explicativo para o frete',
  `enable_global_minimums` tinyint(1) DEFAULT 1 COMMENT 'Se 1 respeita m├¡nimos dos produtos, se 0 ignora todos os m├¡nimos',
  `minimum_explanation_text` varchar(255) DEFAULT 'Vendemos somente no m├¡nimo especificado' COMMENT 'Texto explicativo para m├¡nimos',
  PRIMARY KEY (`id`),
  KEY `idx_store_shipping_enabled` (`enable_shipping`),
  KEY `idx_store_minimums_enabled` (`enable_global_minimums`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_settings`
--

LOCK TABLES `store_settings` WRITE;
/*!40000 ALTER TABLE `store_settings` DISABLE KEYS */;
INSERT INTO `store_settings` VALUES (1,'Gr├úos S.A','Produtos naturales','5545998259993',1500.00,'Av. Beira Rio 555 - Foz do IguaÔö£┬║u - Parana - Brasil','Segunda a Sexta: 8h Ôö£├ís 18h | SÔö£├¡bado: 8h Ôö£├ís 12h','uploads/logos/logo_686c53d1e2264.png','','','','2025-07-11 20:45:14','Consultar con el vendedor',10,1,0,'Frete calculado automaticamente',0,'Vendemos somente no m├¡nimo especificado');
/*!40000 ALTER TABLE `store_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrador','jhonatan@grupo-dip.com','$2y$10$IP1Ryee8ErvgbpkczAVd7.fdeouh1pn9mZ45DUb0dt3Mh1ef5GWzq','admin','2025-05-20 00:11:15','2025-05-20 12:40:44');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_logs`
--

DROP TABLE IF EXISTS `webhook_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `webhook_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT 'ID do pedido relacionado',
  `payload` text DEFAULT NULL COMMENT 'Dados enviados no webhook',
  `response_code` int(11) DEFAULT NULL COMMENT 'CÔö£Ôöédigo de resposta HTTP',
  `response_body` text DEFAULT NULL COMMENT 'Corpo da resposta',
  `error_message` text DEFAULT NULL COMMENT 'Mensagem de erro se houver',
  `success` tinyint(1) DEFAULT 0 COMMENT 'Se o webhook foi enviado com sucesso',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_success` (`success`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_logs`
--

LOCK TABLES `webhook_logs` WRITE;
/*!40000 ALTER TABLE `webhook_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_settings`
--

DROP TABLE IF EXISTS `webhook_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `webhook_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enabled` tinyint(1) DEFAULT 0 COMMENT 'Se o webhook estÔö£├¡ ativo',
  `webhook_url` varchar(500) DEFAULT '' COMMENT 'URL para envio do webhook',
  `secret_key` varchar(255) DEFAULT '' COMMENT 'Chave secreta para autenticaÔö£┬║Ôö£├║o',
  `timeout` int(11) DEFAULT 30 COMMENT 'Timeout em segundos',
  `retry_attempts` int(11) DEFAULT 3 COMMENT 'Tentativas de reenvio',
  `retry_delay` int(11) DEFAULT 5 COMMENT 'Delay entre tentativas (segundos)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_settings`
--

LOCK TABLES `webhook_settings` WRITE;
/*!40000 ALTER TABLE `webhook_settings` DISABLE KEYS */;
INSERT INTO `webhook_settings` VALUES (1,0,'','',30,3,5,'2025-07-12 00:43:37','2025-07-12 00:43:37');
/*!40000 ALTER TABLE `webhook_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'catalogo_graos'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-12 16:54:41
