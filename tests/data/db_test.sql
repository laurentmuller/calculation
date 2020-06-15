BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "sy_Category" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"code"	        varchar(30) NOT NULL,
	"description"	varchar(255) DEFAULT NULL
);
CREATE TABLE IF NOT EXISTS "sy_CategoryMargin" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"category_id"	integer NOT NULL,
	"minimum"	    double NOT NULL DEFAULT '0',
	"maximum"	    double NOT NULL DEFAULT '0',
	"margin"	    double NOT NULL DEFAULT '0',
    FOREIGN KEY(category_id) REFERENCES sy_Category(id)
);

CREATE TABLE IF NOT EXISTS "sy_Product" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"category_id"	integer NOT NULL,
	"description"	varchar(255) NOT NULL,
	"unit"	        varchar(15) DEFAULT NULL,
	"price"	        double NOT NULL DEFAULT '0',
	"supplier"	    varchar(255) DEFAULT NULL,
    FOREIGN KEY(category_id) REFERENCES sy_Category(id)
);

CREATE TABLE IF NOT EXISTS "sy_CalculationState" (
	"id"	        integer AUTO_INCREMENT,
	"code"	        varchar(30) NOT NULL,
	"description"	varchar(255) DEFAULT NULL,
	"editable"	    tinyint(1) DEFAULT '1',
	"color"	        varchar(10) DEFAULT '#000000'
);

CREATE TABLE IF NOT EXISTS "sy_Calculation" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"customer"	    varchar(255),
	"description"	varchar(255),
	"date"	        date DEFAULT NULL,
	"state_id"	    integer NOT NULL,
	"userMargin"	double DEFAULT '0',
	"globalMargin"	double DEFAULT '0',
	"itemsTotal"	double DEFAULT '0',
	"overallTotal"	double DEFAULT '0',
	"created_at"	datetime DEFAULT NULL,
	"created_by"	varchar(255) DEFAULT NULL,
	"updated_at"	datetime DEFAULT NULL,
	"updated_by"	varchar(255) DEFAULT NULL,
    FOREIGN KEY(state_id) REFERENCES sy_CalculationState(id)
);
CREATE TABLE IF NOT EXISTS "sy_CalculationGroup" (
	"id"	            integer PRIMARY KEY AUTOINCREMENT,
	"calculation_id"	integer NOT NULL,
	"category_id"	    integer NOT NULL,
	"code"	            varchar(30) NOT NULL,
	"margin"	        double NOT NULL DEFAULT '0',
	"amount"	        double NOT NULL DEFAULT '0',
    FOREIGN KEY(calculation_id) REFERENCES sy_Calculation(id),
    FOREIGN KEY(category_id) REFERENCES sy_Category(id)
);
CREATE TABLE IF NOT EXISTS "sy_CalculationItem" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"group_id"	    integer NOT NULL,
	"description"   varchar(255) NOT NULL,
	"unit"	        varchar(15) DEFAULT NULL,
	"price"	        double NOT NULL DEFAULT '0',
	"quantity"	    double NOT NULL DEFAULT '0',
    FOREIGN KEY(group_id) REFERENCES sy_CalculationGroup(id)
);

CREATE TABLE IF NOT EXISTS "sy_GlobalMargin" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"minimum"       double NOT NULL DEFAULT '0',
	"maximum"	    double NOT NULL DEFAULT '0',
	"margin"	    double NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS "sy_Property" (
	"id"	        integer PRIMARY KEY AUTOINCREMENT,
	"name"	        varchar(50) NOT NULL,
	"value"	        varchar(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS "sy_User" (
	"id"	                integer PRIMARY KEY AUTOINCREMENT,
	"email"	                varchar(180) NOT NULL,
	"username"	            varchar(180) NOT NULL,
	"username_canonical"    varchar(180) NOT NULL,
	"email_canonical"	    varchar(180) NOT NULL,
	"enabled"	            tinyint(1) DEFAULT '1',
	"salt"	                varchar(255) DEFAULT NULL,
	"password"	            varchar(255) DEFAULT NULL,
	"last_login"	        datetime DEFAULT NULL,
	"confirmation_token"	varchar(180) DEFAULT NULL,
	"password_requested_at"	datetime DEFAULT NULL,
	"roles"	                longtext COMMENT '(DC2Type:array)',
	"rights"	            varchar(20) DEFAULT NULL,
	"overwrite"	            tinyint(1) DEFAULT '0',
	"image_name"	        varchar(255) DEFAULT NULL,
	"updated_at"	        datetime DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS "sy_Migration" (
  "version" varchar(14) PRIMARY KEY,
  "executed_at" datetime COMMENT '(DC2Type:datetime_immutable)'
);


INSERT INTO "sy_User" VALUES (1,'role_super_admin@test.com','role_super_admin','role_super_admin','role_super_admin@test.com',1,NULL,'role_super_admin',NULL,NULL,NULL,'a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";}',NULL,0,NULL,NULL);
INSERT INTO "sy_User" VALUES (2,'role_admin@test.com','role_admin','role_admin','role_admin@test.com',1,NULL,'role_admin',NULL,NULL,NULL,'a:1:{i:0;s:10:"ROLE_ADMIN";}',NULL,0,NULL,NULL);
INSERT INTO "sy_User" VALUES (3,'role_user@test.com','role_user','role_user','role_user@test.com',1,NULL,'role_user',NULL,NULL,NULL,'a:0:{}',NULL,0,NULL,NULL);
INSERT INTO "sy_User" VALUES (4,'role_disabled@test.com','role_disabled','role_disabled','role_disabled',0,'','role_disabled','','','','a:0:{}',NULL,0,NULL,NULL);

COMMIT;
