BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "sy_CalculationGroup" (
	"id"	            integer PRIMARY KEY AUTOINCREMENT,
	"calculation_id"	integer NOT NULL,
	"category_id"	    integer NOT NULL,
	"code"	            varchar(30) NOT NULL,
	"margin"	        double NOT NULL DEFAULT '0',
	"amount"	        double NOT NULL DEFAULT '0'
);
CREATE TABLE IF NOT EXISTS "sy_Product" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"category_id"	integer NOT NULL,
	"description"	varchar(255) NOT NULL,
	"unit"	        varchar(15) DEFAULT NULL,
	"price"	        double NOT NULL DEFAULT '0',
	"supplier"	    varchar(255) DEFAULT NULL
);
CREATE TABLE IF NOT EXISTS "sy_GlobalMargin" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"minimum"       double NOT NULL DEFAULT '0',
	"maximum"	    double NOT NULL DEFAULT '0',
	"margin"	    double NOT NULL DEFAULT '0'
);
CREATE TABLE IF NOT EXISTS "sy_CategoryMargin" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"category_id"	integer NOT NULL,
	"minimum"	    double NOT NULL DEFAULT '0',
	"maximum"	    double NOT NULL DEFAULT '0',
	"margin"	    double NOT NULL DEFAULT '0'
);
CREATE TABLE IF NOT EXISTS "sy_Category" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"code"	        varchar(30) NOT NULL,
	"description"	varchar(255) DEFAULT NULL
);
CREATE TABLE IF NOT EXISTS "sy_CalculationItem" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"group_id"	    integer NOT NULL,
	"description"   varchar(255) NOT NULL,
	"unit"	        varchar(15) DEFAULT NULL,
	"price"	        double NOT NULL DEFAULT '0',
	"quantity"	    double NOT NULL DEFAULT '0'
);
CREATE TABLE IF NOT EXISTS "sy_CalculationState" (
	"id"	        integer AUTO_INCREMENT,
	"code"	        varchar(30),
	"description"	varchar(255),
	"editable"	    tinyint(1) DEFAULT '1',
	"color"	        varchar(10) DEFAULT '#000000'
);
CREATE TABLE IF NOT EXISTS "sy_Property" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"name"	        varchar(50),
	"value"	        varchar(255)
);
CREATE TABLE IF NOT EXISTS "sy_Calculation" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"customer"	    varchar(255),
	"description"	varchar(255),
	"date"	        date,
	"state_id"	    integer,
	"userMargin"	double DEFAULT '0',
	"globalMargin"	double DEFAULT '0',
	"itemsTotal"	double DEFAULT '0',
	"overallTotal"	double DEFAULT '0',
	"created_at"	datetime,
	"created_by"	varchar(255),
	"updated_at"	datetime,
	"updated_by"	varchar(255)
);
CREATE TABLE IF NOT EXISTS "sy_User" (
	"id"	                integer PRIMARY KEY AUTOINCREMENT,
	"email"	                varchar(180),
	"username"	            varchar(180),
	"username_canonical"    varchar(180),
	"email_canonical"	    varchar(180),
	"enabled"	            tinyint(1),
	"salt"	                varchar(255),
	"password"	            varchar(255),
	"last_login"	        datetime DEFAULT NULL,
	"confirmation_token"	varchar(180),
	"password_requested_at"	datetime,
	"roles"	                longtext COMMENT '(DC2Type:array)',
	"rights"	            varchar(20),
	"overwrite"	            tinyint(1),
	"image_name"	        varchar(255),
	"updated_at"	        datetime
);
INSERT INTO "sy_User" VALUES (1,'role_super_admin@test.com','role_super_admin','role_super_admin','role_super_admin@test.com',1,NULL,'role_super_admin',NULL,NULL,NULL,'a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";}',NULL,0,NULL,NULL);
INSERT INTO "sy_User" VALUES (2,'role_admin@test.com','role_admin','role_admin','role_admin@test.com',1,NULL,'role_admin',NULL,NULL,NULL,'a:1:{i:0;s:10:"ROLE_ADMIN";}',NULL,0,NULL,NULL);
INSERT INTO "sy_User" VALUES (3,'role_user@test.com','role_user','role_user','role_user@test.com',1,NULL,'role_user',NULL,NULL,NULL,'a:0:{}',NULL,0,NULL,NULL);
INSERT INTO "sy_User" VALUES (4,'role_disabled@test.com','role_disabled','role_disabled','role_disabled',0,'','role_disabled','','','','a:0:{}',NULL,0,NULL,NULL);
COMMIT;
