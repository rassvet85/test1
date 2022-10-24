-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS "users";
CREATE TABLE "users" (
  "id" int4 NOT NULL DEFAULT nextval('users_id_seq'::regclass),
  "email" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "password" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "user_hash" varchar(32) COLLATE "pg_catalog"."default"
)
;

-- ----------------------------
-- Primary Key structure for table users
-- ----------------------------
ALTER TABLE "users" ADD CONSTRAINT "users_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Table structure for people
-- ----------------------------
DROP TABLE IF EXISTS "people";
CREATE TABLE "people" (
  "id" int4 NOT NULL,
  "username" varchar(255) COLLATE "pg_catalog"."default"
)
;

-- ----------------------------
-- Primary Key structure for table people
-- ----------------------------
ALTER TABLE "people" ADD CONSTRAINT "people_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Table structure for pets
-- ----------------------------
DROP TABLE IF EXISTS "pets";
CREATE TABLE "pets" (
  "pet_code" varchar(3) COLLATE "pg_catalog"."default" NOT NULL,
  "id_people" int4 NOT NULL,
  "pet_type" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "pet_gender" varchar(1) COLLATE "pg_catalog"."default",
  "pet_age" float4 NOT NULL,
  "pet_nickname" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "pet_breed" varchar(255) COLLATE "pg_catalog"."default",
  "pet_rewards" text COLLATE "pg_catalog"."default",
  "pet_parents" text COLLATE "pg_catalog"."default"
)
;
