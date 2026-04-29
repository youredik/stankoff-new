--
-- PostgreSQL database dump
--

\restrict e6MWZGhFifdRO9Oq7OsRkADs7yUofWU6aHBYbJ2AvUM5hSAJ1aHGiOG5aXbzm9j

-- Dumped from database version 16.13
-- Dumped by pg_dump version 16.13

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY "public"."support_ticket_media" DROP CONSTRAINT IF EXISTS "fk_79a7a0bac6d2dc64";
ALTER TABLE IF EXISTS ONLY "public"."support_ticket_comment" DROP CONSTRAINT IF EXISTS "fk_51ec784fc6d2dc64";
ALTER TABLE IF EXISTS ONLY "public"."support_ticket_comment" DROP CONSTRAINT IF EXISTS "fk_51ec784fa76ed395";
ALTER TABLE IF EXISTS ONLY "public"."support_ticket" DROP CONSTRAINT IF EXISTS "fk_1f5a4d53a76ed395";
DROP INDEX IF EXISTS "public"."uniq_8d93d649e7927c74";
DROP INDEX IF EXISTS "public"."idx_79a7a0bac6d2dc64";
DROP INDEX IF EXISTS "public"."idx_51ec784fc6d2dc64";
DROP INDEX IF EXISTS "public"."idx_51ec784fa76ed395";
DROP INDEX IF EXISTS "public"."idx_1f5a4d53a76ed395";
ALTER TABLE IF EXISTS ONLY "public"."user" DROP CONSTRAINT IF EXISTS "user_pkey";
ALTER TABLE IF EXISTS ONLY "public"."support_ticket" DROP CONSTRAINT IF EXISTS "support_ticket_pkey";
ALTER TABLE IF EXISTS ONLY "public"."support_ticket_media" DROP CONSTRAINT IF EXISTS "support_ticket_media_pkey";
ALTER TABLE IF EXISTS ONLY "public"."support_ticket_comment" DROP CONSTRAINT IF EXISTS "support_ticket_comment_pkey";
ALTER TABLE IF EXISTS ONLY "public"."doctrine_migration_versions" DROP CONSTRAINT IF EXISTS "doctrine_migration_versions_pkey";
DROP SEQUENCE IF EXISTS "public"."user_id_seq";
DROP TABLE IF EXISTS "public"."user";
DROP SEQUENCE IF EXISTS "public"."support_ticket_media_id_seq";
DROP TABLE IF EXISTS "public"."support_ticket_media";
DROP SEQUENCE IF EXISTS "public"."support_ticket_id_seq";
DROP SEQUENCE IF EXISTS "public"."support_ticket_comment_id_seq";
DROP TABLE IF EXISTS "public"."support_ticket_comment";
DROP TABLE IF EXISTS "public"."support_ticket";
DROP TABLE IF EXISTS "public"."doctrine_migration_versions";
--
-- Name: SCHEMA "public"; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA "public" IS 'standard public schema';


SET default_tablespace = '';

SET default_table_access_method = "heap";

--
-- Name: doctrine_migration_versions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "public"."doctrine_migration_versions" (
    "version" character varying(191) NOT NULL,
    "executed_at" timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    "execution_time" integer
);


--
-- Name: support_ticket; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "public"."support_ticket" (
    "id" integer NOT NULL,
    "subject" character varying(255) NOT NULL,
    "description" "text" NOT NULL,
    "author_name" character varying(255) NOT NULL,
    "created_at" timestamp(0) without time zone NOT NULL,
    "order_id" integer,
    "order_data" "json",
    "process_instance_key" character varying(255) DEFAULT NULL::character varying,
    "user_id" integer,
    "closed_at" timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    "contractor" character varying(255) DEFAULT NULL::character varying,
    "status" character varying(255) DEFAULT NULL::character varying NOT NULL,
    "accepted_at" timestamp(0) without time zone DEFAULT NULL::timestamp without time zone
);


--
-- Name: support_ticket_comment; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "public"."support_ticket_comment" (
    "id" integer NOT NULL,
    "comment" "text" NOT NULL,
    "closing_reason" character varying(255) DEFAULT NULL::character varying,
    "status" character varying(255) NOT NULL,
    "created_at" timestamp(0) without time zone NOT NULL,
    "support_ticket_id" integer NOT NULL,
    "user_id" integer
);


--
-- Name: support_ticket_comment_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "public"."support_ticket_comment_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: support_ticket_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "public"."support_ticket_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: support_ticket_media; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "public"."support_ticket_media" (
    "id" integer NOT NULL,
    "filename" character varying(255) NOT NULL,
    "original_name" character varying(255) NOT NULL,
    "mime_type" character varying(255) NOT NULL,
    "size" bigint NOT NULL,
    "path" character varying(500) NOT NULL,
    "created_at" timestamp(0) without time zone NOT NULL,
    "support_ticket_id" integer NOT NULL,
    "thumbnail_path" character varying(500) DEFAULT NULL::character varying
);


--
-- Name: support_ticket_media_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "public"."support_ticket_media_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "public"."user" (
    "id" integer NOT NULL,
    "email" character varying(255) NOT NULL,
    "first_name" character varying(255) NOT NULL,
    "last_name" character varying(255) NOT NULL
);


--
-- Name: user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "public"."user_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Data for Name: doctrine_migration_versions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY "public"."doctrine_migration_versions" ("version", "executed_at", "execution_time") FROM stdin;
DoctrineMigrations\\Version20260114084905	2026-01-20 18:10:54	114
DoctrineMigrations\\Version20260114085316	2026-01-20 18:10:54	0
DoctrineMigrations\\Version20260119085938	2026-01-20 18:10:54	73
DoctrineMigrations\\Version20260119131000	2026-01-20 18:10:54	1
DoctrineMigrations\\Version20260120080000	2026-01-20 18:10:54	1
DoctrineMigrations\\Version20260126081923	2026-01-27 07:44:37	37
DoctrineMigrations\\Version20260128073755	2026-01-30 09:16:48	14
DoctrineMigrations\\Version20260211120000	2026-02-11 09:59:05	13
\.


--
-- Data for Name: support_ticket; Type: TABLE DATA; Schema: public; Owner: -
--

COPY "public"."support_ticket" ("id", "subject", "description", "author_name", "created_at", "order_id", "order_data", "process_instance_key", "user_id", "closed_at", "contractor", "status", "accepted_at") FROM stdin;
59	Калибровка станка	Не калибруется станок	Дмитрий Мыслюк	2026-02-11 07:14:27	27209	{"selectedItems":["50485_product"],"contactName":"\\u041e\\u043b\\u0435\\u0433","contactPhone":"+7(999)785-43-12","contactEmail":""}	\N	4	2026-02-12 07:08:45	ООО "СТРОЙАРТ"	completed	2026-02-11 09:19:46
72	Ошибка	На станке появилась ошибка, прошу направить запрос на завод.	Дмитрий Мыслюк	2026-02-17 10:58:22	30795	{"selectedItems":["58345_product"],"contactName":"\\u041d\\u0438\\u043a\\u0438\\u0442\\u0430","contactPhone":"89196399426","contactEmail":""}	\N	3	2026-02-17 11:27:22	ООО "ТРАНСПРОЕКТИЗЫСКАНИЯ"	completed	2026-02-17 11:22:54
66	Есть вопросы по комплектации  Шлифовальный-полировальный станок PR1000-6	Поставил им станок Шлифовальный-полировальный станок PR1000-6\r\nесть вопрос о том.что резинки имеют трещины...\r\nПросит дозаказать..\r\nГ.Чебоксары	Али Шарафутдинов	2026-02-13 12:16:08	31251	{"selectedItems":["59625_product"],"contactName":"\\u041c\\u0438\\u0445\\u0430\\u0438\\u043b","contactPhone":"7 927 995-01-01","contactEmail":""}	\N	3	2026-02-16 10:03:22	ООО "ВИП"	completed	2026-02-13 13:31:26
78	Проблемы с работой лазерного станка	Источник не выдает заявленную мощность. Вместо 12 всего лишь 4	Равиль Камалутдинов	2026-02-20 06:19:55	22515	{"selectedItems":["41405_product"],"contactName":"\\u0415\\u043a\\u0430\\u0442\\u0435\\u0440\\u0438\\u043d\\u0430","contactPhone":"+7 961 018-67-53","contactEmail":""}	\N	3	2026-02-20 08:26:07	ООО "КОМПАНИЯ "СПЕЦПРИЦЕП"	completed	2026-02-20 06:34:15
5	Тест	Описание	Сергей	2026-01-23 13:06:17	31245	{"selectedItems":["59611_product"],"contactName":"\\u0424\\u0430\\u043c\\u0438\\u043b\\u0438\\u044f \\u0418\\u043c\\u044f","contactPhone":"+7788522152","contactEmail":""}	\N	2	2026-02-05 11:28:24	\N	completed	2026-01-23 13:08:05
36	Схема строповки	Прошу направить запрос на завод, касательно предоставления схемы строповки станка и условий его транспортировки.	Дмитрий Мыслюк	2026-02-03 11:03:34	31137	{"selectedItems":["59388_product"],"contactName":"\\u0414\\u043c\\u0438\\u0442\\u0440\\u0438\\u0439","contactPhone":"89958881056","contactEmail":""}	\N	3	2026-02-04 06:53:08	ООО "ПРИЦЕПЦЕНТР"	completed	2026-02-03 12:14:19
40	Неполадки в работе станка	Запросите у клиента фото.	Дмитрий Мыслюк	2026-02-04 08:07:32	29679	{"selectedItems":["55246_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0435\\u0439 \\u041b\\u0435\\u043e\\u043d\\u0438\\u0434\\u043e\\u0432\\u0438\\u0447 \\u041a\\u043e\\u0436\\u0435\\u0432\\u043d\\u0438\\u043a\\u043e\\u0432","contactPhone":"+79127568080","contactEmail":""}	\N	3	2026-02-04 13:52:15	ООО "ИАЗ"	completed	2026-02-04 08:08:55
44	Запрос зап. частей	Прошу сообщить клиенту какая оптика установлена в лазерной голове. Линзы и защитки.	Дмитрий Мыслюк	2026-02-04 12:19:25	21052	{"selectedItems":["38692_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0435\\u0439","contactPhone":"+79307458495","contactEmail":""}	\N	3	2026-02-04 14:00:42	ООО "ЗСО"	completed	2026-02-04 12:51:29
48	Проблема с труборезом	Поправили скорость но ошибка была вот такая	Дмитрий Мыслюк	2026-02-06 06:16:18	18125	{"selectedItems":["32672_product"],"contactName":"\\u0415\\u0432\\u0433\\u0435\\u043d\\u0438\\u0439","contactPhone":"+79379580795","contactEmail":""}	\N	3	2026-02-06 10:43:15	ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ "НЕЙТИ"	completed	2026-02-06 06:19:35
67	Греется	Резко перестал варить сегодня утром, думали стекло, поменяли, не помогло, на курок нажимаешь луч еле еле виден но при этом пистолет очень сильно греется.	Виктор Карасёв	2026-02-16 04:56:42	22895	{"selectedItems":["42067_product"],"contactName":"\\u0414\\u043c\\u0438\\u0442\\u0440\\u0438\\u0439","contactPhone":"+7 900 055 8728","contactEmail":""}	\N	4	2026-02-16 14:43:14	ИП Анкудинова Анастасия Алексеевна	completed	2026-02-16 10:26:08
61	Идёт дым	При осмотре из катушки пошёл дым	Виктор Карасёв	2026-02-12 07:28:24	30187	{"selectedItems":["57094_product"],"contactName":"\\u0421\\u0435\\u0440\\u0433\\u0435\\u0439","contactPhone":"79677579124","contactEmail":""}	\N	4	2026-02-16 07:08:47	АО "ГОРОД"	completed	2026-02-12 07:29:12
79	ИП ШАКИРОВ С.А	Сломался пластиковый сторопор ШВП по Z. выпали шарики, прошу рассмотреть и выдать рекомендации клиенту по оперативному решению вопроса.	Дмитрий Мыслюк	2026-02-20 07:46:20	30210	{"selectedItems":["57148_product"],"contactName":"\\u0414\\u043c\\u0438\\u0442\\u0440\\u0438\\u0439","contactPhone":"+79524329122","contactEmail":""}	\N	4	2026-02-20 08:16:13	ИП Шакиров Сергей Андреевич	completed	2026-02-20 08:00:16
52	Ошибка ёмкость	Не калибруется ёмкость	Виктор Карасёв	2026-02-06 12:29:49	27539	{"selectedItems":["51090_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0435\\u0439","contactPhone":"+79778310139","contactEmail":""}	\N	4	2026-02-06 12:31:55	ООО "К-РОБОТИКС"	completed	2026-02-06 12:31:16
11	ООО "КОМЕТА"	Неполадки с кромкооблицовочным станком	Дмитрий Мыслюк	2026-01-26 11:06:45	28741	{"selectedItems":["53298_product"],"contactName":"\\u0412\\u0430\\u0434\\u0438\\u043c","contactPhone":"+7 927 736-11-17","contactEmail":""}	\N	4	2026-02-09 05:07:44	\N	completed	2026-01-26 11:54:56
4	Ошибка в работе конвейерной системы подачи	Клиент сообщает о проблеме с запуском станка после последнего обслуживания. Станок не реагирует на команды управления.	Эдуард Сарваров	2026-01-21 06:40:45	31245	{"selectedItems":["59611_product"],"contactName":"\\u041f\\u0443\\u043f\\u043a\\u0438\\u043d \\u0412\\u0430\\u0441\\u0438\\u043b\\u0438\\u0439","contactPhone":"9437542345","contactEmail":"test@test.ru"}	\N	2	2026-02-10 06:02:20	\N	completed	2026-01-22 09:56:36
56	Наладка узла подрезной пилы	У клиента "поплыла" подрезная пила. гудит подшипник. Необходим или мануал, или рекомендации по замене подшипника. \r\nКлиент с Иркутска, при звонке прошу учитывать разницу во времени.	Равиль Камалутдинов	2026-02-09 14:18:08	23965	{"selectedItems":["44153_product"],"contactName":"\\u0421\\u043e\\u0431\\u043e\\u043b\\u0435\\u0432 \\u0418\\u0432\\u0430\\u043d \\u0410\\u043b\\u0435\\u043a\\u0441\\u0430\\u043d\\u0434\\u0440\\u043e\\u0432\\u0438\\u0447","contactPhone":"79294344711","contactEmail":""}	\N	3	2026-02-10 12:23:00	Соболев Иван Александрович	completed	2026-02-09 15:04:20
38	Настройка станка	У клиента вопрос по настройке станка. Прошу направить рекомендации.	Дмитрий Мыслюк	2026-02-04 05:39:58	19730	{"selectedItems":["35861_product"],"contactName":"\\u0421\\u0443\\u043b\\u0442\\u0430\\u043d\\u0431\\u0435\\u043a","contactPhone":"+79873452665","contactEmail":""}	\N	3	2026-02-04 09:18:54	ИП Жунинбаев Султанбек Сулейманович	completed	2026-02-04 09:13:13
73	Сложности с сборкой станкаи ML393A	Ренат, добрый день. Возникли вопросы при сборке станка, может ваши специалисты собирали такой станок мл 393 а. 1.как крепится сверлильный патрон к валу, нет ни шпонки, ни резьбы-одевается на конус. 2. Нет переходника на крепление точильный дисков разных внутренних диаметром. 3. Пильный диск через шайбу не плотно фиксируется к валу, фланцы без фрезеровки (папа мама). Пока всё. Владимир	Ренат Гарифьянов	2026-02-18 06:29:48	31381	{"selectedItems":["60010_product"],"contactName":"\\u041c\\u0438\\u0445\\u0430\\u043b\\u043a\\u0438\\u043d \\u0412\\u043b\\u0430\\u0434\\u0438\\u043c\\u0438\\u0440 \\u041f\\u0435\\u0442\\u0440\\u043e\\u0432\\u0438\\u0447","contactPhone":"+7 927 197-11-04","contactEmail":""}	\N	3	2026-02-20 08:00:07	Михалкин Владимир Петрович	completed	2026-02-18 07:29:30
62	Неисправность станка	При работе на станке появился запах гари и дым, прошу связаться с клиентом и выяснить причины случившегося.\r\nСтанок на гарантии, поставщик КАМИ.\r\nМожно связаться с ними для выяснения причин случившегося.	Дмитрий Мыслюк	2026-02-12 07:55:14	28564	{"selectedItems":["53039_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0430\\u043d\\u0434\\u0440","contactPhone":"+7 937 378-41-18","contactEmail":""}	\N	3	2026-02-12 13:19:08	ООО НПП "МАКС21"	completed	2026-02-12 09:10:19
57	Есть вопросы по работе "Гильотина для шпона MD310"	Есть вопросы по работе "Гильотина для шпона MD310" \r\nПросят код...	Али Шарафутдинов	2026-02-10 06:10:14	20730	{"selectedItems":["38010_product"],"contactName":"\\u0418\\u0433\\u0440\\u044c \\u041a\\u0440\\u0443\\u0442\\u0438\\u043b\\u043e\\u0432","contactPhone":"79038099229","contactEmail":""}	\N	3	2026-02-16 06:38:13	ИП Соклеткин Андрей Павлович	completed	2026-02-10 08:12:57
68	Не работает узел гидравлики	https://workspace.stankoff.ru/task/view/12449	Виктор Карасёв	2026-02-16 06:43:30	28647	{"selectedItems":["53159_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0430\\u043d\\u0434\\u0440","contactPhone":"79125639909","contactEmail":""}	\N	4	2026-02-16 06:50:35	МУП "ЭКОСЕРВИС"	completed	2026-02-16 06:48:34
6	Проверка	Проверка	Эдуард Сарваров	2026-01-23 13:13:48	31282	{"selectedItems":["59705_product"],"contactName":"\\u041f\\u0443\\u043f\\u043a\\u0438\\u043d \\u0412\\u0430\\u0441\\u0438\\u043b\\u0438\\u0439","contactPhone":"+79437542345","contactEmail":"test@test.ru"}	\N	4	2026-01-26 06:34:21	\N	completed	2026-01-26 06:34:11
74	Запрос техподдержки ООО Альтаир кромочник w2	подсказать по блоку подготовки воздуха	Динар Фасхутдинов	2026-02-18 07:14:06	31412	{"selectedItems":["60113_product"],"contactName":"\\u0415\\u0432\\u0433\\u0435\\u043d\\u0438\\u0439","contactPhone":"79202070431","contactEmail":""}	\N	4	2026-02-19 05:26:09	ООО "АЛЬТАИР"	completed	2026-02-18 07:57:03
80	y1 вырывает кулачки	Происходит это так когда у1 разжимает кулачки он одновременно отьежает в рефередный ноль и поворачивает патрон , Бабка сама не успевает отежать и как раз получается что деталь в районе проворачивается	Виктор Карасёв	2026-02-24 06:16:50	25167	{"selectedItems":["46737_product"],"contactName":"\\u0421\\u0442\\u0435\\u0444\\u0430\\u043d","contactPhone":"+7 960 353-21-25","contactEmail":""}	\N	4	2026-02-24 12:23:04	ООО "ЭНГЕЛЬССКИЙ МЕТАЛЛ"	completed	2026-02-24 06:17:19
63	Запрос стабилизатор	Клиент интересуется какой лучше стабилизатор установить.	Дмитрий Мыслюк	2026-02-12 08:30:37	30253	{"selectedItems":["57229_product"],"contactName":"\\u0415\\u0432\\u0433\\u0435\\u043d\\u0438\\u0439","contactPhone":"+79061357376","contactEmail":""}	\N	4	2026-02-12 11:46:57	ООО "ПРОММЕТАЛЛ"	completed	2026-02-12 09:08:06
69	Нет излучения	При нажатии на клавишу излучения не идёт	Виктор Карасёв	2026-02-16 07:49:53	12285	{"selectedItems":["20066_product"],"contactName":"\\u0414\\u0435\\u043d\\u0438\\u0441 \\u041c\\u0430\\u0441\\u043b\\u043e\\u0432","contactPhone":"79045315908","contactEmail":""}	\N	4	2026-02-16 08:53:49	ООО "НИЛЕД"	completed	2026-02-16 08:53:09
75	На углах плохо фрезерует	На углах плохо фрезерует	Сергей Маврин	2026-02-19 07:11:44	31167	{"selectedItems":["59383_product"],"contactName":"\\u041a\\u0440\\u0443\\u0442\\u044c \\u0412\\u0438\\u043a\\u0442\\u043e\\u0440 \\u0412\\u043b\\u0430\\u0434\\u0438\\u043c\\u0438\\u0440\\u043e\\u0432\\u0438\\u0447","contactPhone":"+79256322350","contactEmail":""}	\N	3	2026-02-19 07:22:24	Круть Виктор Владимирович	completed	2026-02-19 07:21:44
17	Решить проблему с резом	Рез по оси Y идёт волной.	Сергей Маврин	2026-01-29 08:55:08	23930	{"selectedItems":["44098_product"],"contactName":"\\u0415\\u0432\\u0433\\u0435\\u043d\\u0438\\u0439 \\u0420\\u043e\\u0436\\u043a\\u043e\\u0432","contactPhone":"+79635123333","contactEmail":"rozhkov-evgeniy@rambler.ru"}	\N	3	2026-01-29 09:00:00	\N	completed	2026-01-29 08:58:03
60	Вибрация передней бабки	Вибрация передней бабки	Дмитрий Мыслюк	2026-02-11 07:57:22	18621	{"selectedItems":["33656_product"],"contactName":"\\u0421\\u0435\\u0440\\u0433\\u0435\\u0439 \\u0412\\u0430\\u043b\\u0435\\u0440\\u044c\\u0435\\u0432\\u0438\\u0447","contactPhone":"+79105017011","contactEmail":""}	\N	3	2026-02-12 07:30:50	ООО "КОМФУР"	completed	2026-02-11 08:41:09
76	Ломает сверла об лапу	Ломает сверла об лапу\r\n\r\nНе постоянно \r\nПол дня все хорошо сверлит нормально потом станок решет лапу не переставлять и сверлит прямо в лапу\r\n\r\nПосле замены сверла все так же какую то часть времени сверлит нормально и в моменте опять начинает сверлить лапу	Дмитрий Мыслюк	2026-02-19 07:17:08	30795	{"selectedItems":["58345_product"],"contactName":"\\u041d\\u0438\\u043a\\u0438\\u0442\\u0430","contactPhone":"89196399426","contactEmail":""}	\N	3	2026-02-20 14:23:48	ООО "ТРАНСПРОЕКТИЗЫСКАНИЯ"	completed	2026-02-19 11:36:52
64	теряется ёмкость	теряется ёмкость	Виктор Карасёв	2026-02-12 08:52:56	27539	{"selectedItems":["51090_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0435\\u0439","contactPhone":"+79031676166","contactEmail":""}	\N	4	2026-02-13 11:09:47	ООО "К-РОБОТИКС"	completed	2026-02-12 09:31:42
70	Выходит ошибка на мониторе	Выходит ошибка на мониторе	Маргарита Якупова	2026-02-16 10:03:00	25643	{"selectedItems":["47587_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0430\\u043d\\u0434\\u0440 \\u0410\\u043b\\u0435\\u043a\\u0441\\u0430\\u043d\\u0434\\u0440\\u043e\\u0432\\u0438\\u0447 \\u041d\\u0435\\u043d\\u0430\\u0445\\u043e\\u0432","contactPhone":"+7 910 650-14-98","contactEmail":"director.tmb@atesy.info"}	\N	4	2026-02-16 10:52:45	ООО "ТЕХНОЛОГИИ УПРАВЛЕНИЯ"	completed	2026-02-16 10:04:59
37	Подготовка в ПНР	Прошу проинструктировать клиента по подготовке станка к ПНР.\r\nРазмещение станка, электричество, лубрикаторное масло 1 л, какое давление воздуха, заготовки и т.д.	Дмитрий Мыслюк	2026-02-03 11:13:35	31255	{"selectedItems":["59637_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0435\\u0439","contactPhone":"89056436520","contactEmail":""}	\N	3	2026-02-03 12:13:19	ООО "ГУД-ВУД+"	completed	2026-02-03 12:10:17
23	Чернеет клей после подающего вала	Клиент купил кромкооблицовочный станок EB360 D+J\r\nКлей на кромку выходит черный, в самой ванне все чисто. \r\nЧистку ванны проводили	Азат Гатауллин	2026-01-30 11:08:18	30708	{"selectedItems":["58148_product"],"contactName":"\\u041e\\u0432\\u0447\\u0438\\u043d\\u043d\\u0438\\u043a\\u043e\\u0432 \\u0410\\u043b\\u0435\\u043a\\u0441\\u0435\\u0439 \\u0412\\u0438\\u043a\\u0442\\u043e\\u0440\\u043e\\u0432\\u0438\\u0447","contactPhone":"79656133366","contactEmail":""}	\N	3	2026-02-04 14:14:44	Овчинников Алексей Викторович	completed	2026-01-30 11:32:57
53	Источник в ошибке	Нет излучения	Сергей Маврин	2026-02-06 13:26:57	28728	{"selectedItems":["53282_product"],"contactName":"\\u0418\\u043b\\u044c\\u044f \\u0410\\u043b\\u0435\\u043a\\u0441\\u0435\\u0435\\u0432\\u0438\\u0447 \\u041c\\u0430\\u0440\\u0442\\u044b\\u043d\\u043e\\u0432","contactPhone":"+79136392856","contactEmail":"380720@mail.ru"}	\N	3	2026-02-06 13:29:08	ИП Мартынов Илья Алексеевич	completed	2026-02-06 13:27:35
49	нет режет	красная тчока есть . лазера нет	Виктор Карасёв	2026-02-06 07:42:07	27855	{"selectedItems":["51777_product"],"contactName":"\\u0435\\u0433\\u043e\\u0440","contactPhone":"79534135676","contactEmail":""}	\N	3	2026-02-06 12:54:26	ООО "СПЕЦМАШДЕТАЛЬ"	completed	2026-02-06 07:58:16
58	Тема обращения (тест)	Описание	Дмитрий Мыслюк	2026-02-10 06:13:38	29845	{"selectedItems":["56207_product"],"contactName":"\\u0414\\u043c\\u0438\\u0442\\u0440\\u0438\\u0439","contactPhone":"+79372977009","contactEmail":""}	\N	1	2026-02-10 06:14:51	ООО НПО "КОМПАНИЯ "АВИС"	completed	2026-02-10 06:13:50
13	ООО "ТРАНСПРОЕКТИЗЫСКАНИЯ"	Вопрос по кромочнику, прошу связаться с клиентом.\r\nЯ приложил контакты оператора форматки, выйдете через него на оператора кромочника.	Дмитрий Мыслюк	2026-01-28 06:12:55	30794	{"selectedItems":["58344_product"],"contactName":"\\u041d\\u0438\\u043a\\u043e\\u043b\\u0430\\u0439","contactPhone":"+79600442303","contactEmail":""}	\N	3	2026-01-28 08:45:52	\N	completed	2026-01-28 06:21:43
16	Подготовка к Запуску станка	Клиент хочет пообщаться с инженером по техническим вопросам для подготовки к запуску станка	Николай Васильев	2026-01-28 12:35:46	30910	{"selectedItems":["58661_product","58660_product","58659_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0435\\u0439","contactPhone":"79206466364","contactEmail":""}	\N	3	2026-01-28 13:21:46	\N	completed	2026-01-28 12:42:43
1	Не работает система охлаждения на металлообрабатывающем станке	Требуется настройка параметров обработки для нового типа материала. Станок работает, но качество обработки неудовлетворительное. Требуется настройка параметров обработки для нового типа материала. Станок работает, но качество обработки неудовлетворительное. Требуется настройка параметров обработки для нового типа материала. Станок работает, но качество обработки неудовлетворительное. Требуется настройка параметров обработки для нового типа материала. Станок работает, но качество обработки неудовлетворительное.	Эдуард Сарваров	2026-01-20 18:13:30	31245	{"selectedItems":["59611_product"],"contactName":"\\u041f\\u0443\\u043f\\u043a\\u0438\\u043d \\u0412\\u0430\\u0441\\u0438\\u043b\\u0438\\u0439","contactPhone":"9437542345","contactEmail":"test@test.ru"}	\N	2	2026-01-20 18:15:10	\N	completed	2026-01-20 18:14:12
2	Проблема с точностью обработки на фрезерном станке	После замены комплектующих станок работает нестабильно. Необходима проверка всех систем и настройка. После замены комплектующих станок работает нестабильно. Необходима проверка всех систем и настройка. После замены комплектующих станок работает нестабильно. Необходима проверка всех систем и настройка.	Эдуард Сарваров	2026-01-21 06:35:23	31245	{"selectedItems":["59611_product"],"contactName":"\\u041f\\u0443\\u043f\\u043a\\u0438\\u043d \\u0412\\u0430\\u0441\\u0438\\u043b\\u0438\\u0439","contactPhone":"9437542345","contactEmail":"test@test.ru"}	\N	4	2026-01-26 06:33:35	\N	completed	2026-01-21 06:37:30
22	тест	тест	Эдуард Сарваров	2026-01-30 11:07:45	31352	{"selectedItems":["59877_product"],"contactName":"\\u041f\\u0443\\u043f\\u043a\\u0438\\u043d \\u0412\\u0430\\u0441\\u0438\\u043b\\u0438\\u0439","contactPhone":"+79437542345","contactEmail":"test@test.ru"}	\N	1	2026-01-30 12:11:25	Касаткин Руслан Игоревич	completed	2026-01-30 12:07:37
24	еукеу	уке	Дмитрий Мыслюк	2026-01-30 12:10:28	29406	{"selectedItems":["54552_product"],"contactName":"\\u0414\\u043c\\u0438\\u0442\\u0440\\u0438\\u0439","contactPhone":"89372977009","contactEmail":""}	\N	1	2026-01-30 12:31:40	ООО "КАРТЕР"	completed	2026-01-30 12:31:33
54	сбивается	сбивается угол	Виктор Карасёв	2026-02-09 10:41:35	28225	{"selectedItems":["52512_product"],"contactName":"\\u0422\\u0438\\u043c\\u0443\\u0440","contactPhone":"+7 920 006-66-79","contactEmail":""}	\N	4	2026-02-12 07:09:20	ИП Каузова Марина Михайловна	completed	2026-02-09 12:36:16
3	Требуется настройка программного обеспечения для станка	Во время работы станка произошел сбой в системе управления. Необходимо провести диагностику и ремонт. Во время работы станка произошел сбой в системе управления. Необходимо провести диагностику и ремонт. Во время работы станка произошел сбой в системе управления. Необходимо провести диагностику и ремонт.	Эдуард Сарваров	2026-01-21 06:35:45	31245	{"selectedItems":["59611_product"],"contactName":"Sergey Mavrin","contactPhone":"","contactEmail":"sergey.mavrin@stankoff.ru"}	\N	4	2026-01-26 06:33:49	\N	completed	2026-01-21 06:36:46
7	Станок для оптоволоконной лазерной резки, модель XTC-1530GP Raycus 12000W	Ошибка - утечка газа	Альберт Зарипов	2026-01-26 06:23:44	29635	{"selectedItems":["55152_product"],"contactName":"\\u0413\\u043e\\u043b\\u0443\\u0431\\u044c \\u0412\\u043b\\u0430\\u0434\\u0438\\u043c\\u0438\\u0440 \\u0412\\u043b\\u0430\\u0434\\u0438\\u043c\\u0438\\u0440\\u043e\\u0432\\u0438\\u0447","contactPhone":"+7 903 275-59-88","contactEmail":"produce@cubeart.ru"}	\N	4	2026-01-26 08:51:51	\N	completed	2026-01-26 06:34:40
9	XTC-1530H 3000 Raycus	У них гудит редуктор и привода по этому станку, хочет повторный выезд сервисника и желательно по гарантии))	Альберт Зарипов	2026-01-26 08:03:58	23193	{"selectedItems":["42676_product"],"contactName":"\\u0414\\u043c\\u0438\\u0442\\u0440\\u0438\\u0439 \\u041d\\u043e\\u0432\\u0438\\u043a\\u043e\\u0432","contactPhone":"+79274487575","contactEmail":""}	\N	3	2026-01-26 09:48:50	\N	completed	2026-01-26 09:08:34
8	Проблема с пильным центром	Прошу помочь клиент с кареткой. Если помочь удаленно не получится, прошу подключить техподдержку поставщика.	Дмитрий Мыслюк	2026-01-26 07:02:26	30793	{"selectedItems":["58343_product"],"contactName":"\\u041d\\u0438\\u043a\\u043e\\u043b\\u0430\\u0439","contactPhone":"+79600442303","contactEmail":""}	\N	3	2026-01-26 12:40:40	\N	completed	2026-01-26 07:09:31
10	Ошибка на серводрайвере	Купили новый драйвер, заменили, выдает ошибки.	Дмитрий Мыслюк	2026-01-26 08:09:30	14356	{"selectedItems":["24439_product"],"contactName":"\\u0412\\u0430\\u0434\\u0438\\u043c","contactPhone":"+79870162529","contactEmail":""}	\N	4	2026-01-27 06:18:13	\N	completed	2026-01-26 09:04:07
12	Форматно-раскроечный станок модель W6 -пилит бананом	Форматно-раскроечный станок модель W6 -пилит бананом.\r\nПокупал у нас со склада г.Казань без ПНР.	Али Шарафутдинов	2026-01-27 06:25:45	30965	{"selectedItems":["58767_product"],"contactName":"\\u0418\\u0440\\u0435\\u043a","contactPhone":"+79869094891","contactEmail":""}	\N	3	2026-01-27 09:41:29	\N	completed	2026-01-27 09:40:51
14	ООО "НПП "ОРИОН" Вертикально-фрезерный станок OPTImill MH50V s/n: 10782411-001	Клиент обратился с рекламацией. \r\nЗаявленный дефект:\r\nПосле переключения режима подъема фрезерной головы в режим быстрого перемещения происходит следующее:\r\n1. При нажатии клавиши подъема фрезерная голова не перемещается, хотя слышны щелчки с тыльной части станка.\r\n2. При нажатии клавиши опускания фрезерной головы происходит ее перемещение вниз и при отпускании клавиши-ее торможение. Однако, после нажатия кнопки подъема-голова движется вверх на 1-1,5 оборота холодного винта, а затем подъем самопроизвольно останавливается. Последующие последовательные нажатия кнопки подъема фрезерной головы не приводит к подъему механизма. Если, снова нажав на кнопку опускания головы не приводится в движение, затем нажать кнопку вверх, повторяется движение головы вверх на 1-1,5 оборота ходового винта и ее остановка. После установки ручного режима перемещения режущей головы и вращения кривошипной рукоятки-фрезерная голова движется без заеданий, посторонних стуков и переменного сопротивления. \r\n\r\nПрошу связаться с клиентом и выяснить решается ли вопрос дистанционно.	Анастасия Карюхина	2026-01-28 08:00:21	29179	{"selectedItems":["54078_product"],"contactName":"\\u0414\\u0435\\u043c\\u0435\\u043d\\u0435\\u0432 \\u041c\\u0430\\u043a\\u0441\\u0438\\u043c \\u0418\\u0433\\u043e\\u0440\\u0435\\u0432\\u0438\\u0447","contactPhone":"+7 950 023 78 93","contactEmail":"ooo@npp-orion.ru"}	\N	4	2026-01-29 07:56:27	\N	completed	2026-01-28 09:08:05
18	Ошибка на станке	По оси V ошибка. Не обнуляется.	Сергей Маврин	2026-01-29 09:09:01	29135	{"selectedItems":["53967_product"],"contactName":"\\u0412\\u0430\\u0441\\u0438\\u043b\\u0438\\u0439 \\u0412\\u0430\\u0441\\u0438\\u043b\\u044c\\u0435\\u0432\\u0438\\u0447 \\u0414\\u0430\\u043d\\u0438\\u043b\\u044c\\u0446\\u0435\\u0432","contactPhone":"+79127982222","contactEmail":"mart@marts.ru"}	\N	3	2026-01-29 09:13:22	\N	completed	2026-01-29 09:09:24
15	Настройка инструмента	помощь в внесении инструмента на стойку .	Виктор Карасёв	2026-01-28 10:58:17	27232	{"selectedItems":["50515_product"],"contactName":"\\u041e\\u043b\\u0435\\u0433","contactPhone":"+79268861698","contactEmail":""}	\N	4	2026-01-30 05:02:10	\N	completed	2026-01-28 10:58:44
19	MB203C Двухсторонний реймусовый станок проблема с роликом	Купили станок  MB203C Двухсторонний реймусовый станок поставщик ЛТТ Новосиб. \r\nОбнаружили что ролик не ровный, из за этого заготовка ходит на 1мм верх/вниз .	Азат Гатауллин	2026-01-30 05:39:12	30591	{"selectedItems":["57882_product"],"contactName":"\\u0417\\u0430\\u0445\\u0430\\u0440\\u0447\\u0435\\u043d\\u043a\\u043e \\u0421\\u0435\\u0440\\u0433\\u0435\\u0439","contactPhone":"+7 938 479-33-31","contactEmail":""}	\N	4	2026-01-30 06:40:14	Захарченко Юлия Александровна	completed	2026-01-30 06:05:20
29	При первом запуске станок в ошибке	При первом запуске оборудования в день станок стоит в ошибке "z axis encoder has no response "\r\nчерез 10-15 минут ошибка сама сбрасывается и оборудование работает в штатном режиме .	Виктор Карасёв	2026-02-03 05:18:51	29594	{"selectedItems":["55089_product"],"contactName":"","contactPhone":"","contactEmail":""}	\N	4	2026-02-04 05:11:48	ООО "ДОМАРТ"	completed	2026-02-03 05:20:08
26	W9 Форматно-раскроечный станок	Подскажите пожалуйста какое масло нужно заливать в станок?\r\nВ инструкции написано про смазку деталей, но про заливку информации не нашли.	Азат Гатауллин	2026-02-02 08:20:42	31154	{"selectedItems":["59341_product"],"contactName":"\\u041e\\u043b\\u0435\\u0433","contactPhone":"79191001515","contactEmail":""}	\N	3	2026-02-02 08:38:33	ООО "СЗ "АРХФАСАД"	completed	2026-02-02 08:27:43
41	Течет масло	течет масло	Виктор Карасёв	2026-02-04 09:55:51	28573	{"selectedItems":["53052_product"],"contactName":"","contactPhone":"","contactEmail":""}	\N	4	2026-02-05 05:24:39	ООО "СТАЛЬНЫЕ ДВЕРИ "РИКОР"	completed	2026-02-04 10:11:11
20	ООО "КЕДР-1"	Добрый день. Подскажите пжл как на листогибе подсветку включить?	Дмитрий Мыслюк	2026-01-30 06:19:23	30852	{"selectedItems":["58507_product"],"contactName":"\\u041c\\u0430\\u043a\\u0441\\u0438\\u043c","contactPhone":"+7 927 656 8873","contactEmail":""}	\N	3	2026-01-30 13:52:49	ООО "КЕДР-1"	completed	2026-01-30 06:21:36
45	Станок в ошибке	Источник в ошибке	Виктор Карасёв	2026-02-05 08:19:15	29167	{"selectedItems":["54050_product"],"contactName":"\\u041a\\u043b\\u0438\\u0435\\u043d\\u0442","contactPhone":"+79243729821","contactEmail":""}	\N	4	2026-02-05 08:20:40	Манушакян Владислав Артурович	completed	2026-02-05 08:20:31
27	На "Торцовочный станок ЦТ10" разлетелся подшипник	Клиент покупал у нас станок в 2025 году..\r\nНа "Торцовочный станок ЦТ10" разлетелся подшипник.\r\nПросит обратную связь	Али Шарафутдинов	2026-02-02 09:33:53	30875	{"selectedItems":["58566_product"],"contactName":"\\u0412\\u0438\\u043a\\u0442\\u043e\\u0440","contactPhone":"89172275573","contactEmail":""}	\N	4	2026-02-02 10:57:23	ООО "ТОРНАДО ЛАБ"	completed	2026-02-02 10:56:54
28	Не работает программа на компьютере	Не заходит, выдаёт ошибку.	Сергей Маврин	2026-02-02 14:26:22	29135	{"selectedItems":["53967_product"],"contactName":"\\u0412\\u0430\\u0441\\u0438\\u043b\\u0438\\u0439 \\u0412\\u0430\\u0441\\u0438\\u043b\\u044c\\u0435\\u0432\\u0438\\u0447 \\u0414\\u0430\\u043d\\u0438\\u043b\\u044c\\u0446\\u0435\\u0432","contactPhone":"+79127982222","contactEmail":"mart@marts.ru"}	\N	3	2026-02-02 14:30:18	ООО "ГАЛЕРЕЯ"М"	completed	2026-02-02 14:29:47
21	Ошибка на серводрайвере	Ошибка  020 на серводрайвере лидшайн H2-2206 , сервак оси X . Управляется пультом Ричауто А11Е	Виктор Карасёв	2026-01-30 08:25:35	27211	{"selectedItems":["50487_product"],"contactName":"\\u0412\\u0430\\u043b\\u0435\\u0440\\u0438\\u0439","contactPhone":"+79600370017","contactEmail":""}	\N	4	2026-02-03 09:02:36	ООО "СИАРЕНЕ ЯХТС"	completed	2026-01-30 08:26:34
46	станок в ошибке	станок в ошибке (источник)	Виктор Карасёв	2026-02-05 08:22:24	29638	{"selectedItems":["55168_product"],"contactName":"\\u0415\\u043b\\u0435\\u043d\\u0430 \\u043d\\u0438\\u043a\\u043e\\u043b\\u0430\\u0435\\u0432\\u043d\\u0430","contactPhone":"+79243729821","contactEmail":""}	\N	4	2026-02-05 11:41:06	ГАПОУ "КГПТ"	completed	2026-02-05 08:23:13
25	ООО "ЛЮК ДК" Гидравлический Листогибочный Пресс PBS-110Tx3200	Поступила рекламация от менеджера на течь масла у листогибочного пресса PBS-110/3200	Анастасия Карюхина	2026-02-02 07:40:30	28234	{"selectedItems":["52523_product"],"contactName":"\\u0414\\u043c\\u0438\\u0442\\u0440\\u0438\\u0439","contactPhone":"79084777937","contactEmail":"dk.luk@yandex.ru"}	\N	3	2026-02-10 12:40:43	ООО "ЛЮК ДК"	completed	2026-02-02 08:24:54
30	ООО "УЗДМ"	Ошибка сервопривода	Дмитрий Мыслюк	2026-02-03 05:58:58	28202	{"selectedItems":["52473_product"],"contactName":"\\u0412\\u0430\\u043b\\u0435\\u0440\\u0438\\u0439","contactPhone":"+79195776996","contactEmail":""}	\N	4	2026-02-03 08:58:14	ООО "УЗДМ"	completed	2026-02-03 06:25:17
31	Проблема с ПО	проблема с программным обеспечением , когда установили обновление кнопки перестали отвечать за те или иные действия	Виктор Карасёв	2026-02-03 07:53:11	25167	{"selectedItems":["46737_product"],"contactName":"","contactPhone":"","contactEmail":""}	\N	4	2026-02-03 09:31:49	ООО "ЭНГЕЛЬССКИЙ МЕТАЛЛ"	completed	2026-02-03 08:09:04
35	Схема строповки	Прошу направить запрос на завод, касательно предоставления схемы строповки станка и условий его транспортировки.	Дмитрий Мыслюк	2026-02-03 11:02:29	31148	{"selectedItems":["59391_product"],"contactName":"\\u0414\\u043c\\u0438\\u0442\\u0440\\u0438\\u0439","contactPhone":"89958881056","contactEmail":""}	\N	1	2026-02-03 11:03:03	ООО "ПРИЦЕПЦЕНТР"	completed	2026-02-03 11:02:55
39	Ошибка трубореза	труборез с автоподачей выдает такое сообщение	Дмитрий Мыслюк	2026-02-04 05:42:29	28928	{"selectedItems":["53604_product"],"contactName":"\\u0420\\u043e\\u043c\\u0430\\u043d","contactPhone":"+7 903 839-28-39","contactEmail":""}	\N	3	2026-02-04 07:36:33	ООО "КОМФУР"	completed	2026-02-04 06:21:26
50	Проблемы со станком	Датчик измерения инструмента глючит.	Олег Селезнев	2026-02-06 08:11:48	31167	{"selectedItems":["59383_product","59384_product"],"contactName":"\\u0412\\u0438\\u043a\\u0442\\u043e\\u0440","contactPhone":"79256322350","contactEmail":""}	\N	3	2026-02-09 10:33:57	Круть Виктор Владимирович	completed	2026-02-06 14:43:23
42	ИП Расторопов А, станок Dt1000-4	Сюда фазы? Правильно?	Динар Фасхутдинов	2026-02-04 11:02:09	30693	{"selectedItems":["58093_product"],"contactName":"\\u0420\\u0430\\u0441\\u0442\\u043e\\u0440\\u043e\\u043f\\u043e\\u0432 \\u0410\\u043d\\u0430\\u0442\\u043e\\u043b\\u0438\\u0439 \\u041f\\u0430\\u0432\\u043b\\u043e\\u0432\\u0438\\u0447","contactPhone":"79237778118","contactEmail":"kamin_22@mail.ru"}	\N	4	2026-02-05 06:26:30	ИП Расторопов Анатолий Павлович	completed	2026-02-05 06:26:20
34	Листогиб KRASS PBS 135/3200 4 оси Delem 66S	Прошу связаться с клиентом и помочь с настройкой станка. Есть вопросы по скорости движения траверсы. Предварительно, это вопрос с настройкой ЧПУ. Или даже программы детали. Кажется, что где-то пропускают настройку по задержке траверсы в момент гиба. Вряд-ли вопрос связан с механической настройкой...	Данил Киряшин	2026-02-03 10:21:07	29739	{"selectedItems":["55935_product"],"contactName":"\\u041c\\u0443\\u0441\\u043b\\u0438\\u043c","contactPhone":"79388974995","contactEmail":""}	\N	3	2026-02-06 13:14:34	ООО "СТРОЙИНВЕСТ"	completed	2026-02-03 12:45:24
43	ИП Расторопов А, станок Dt1000-4	На левом блоке шлейф не подключен, просто выпал? Лежит под ним\r\nЭто под воздух или масло? Что с этим делать?\r\nРегулировка натяжения ленты, я так понял. Отверстия есть, но сам шток не зафиксирован, какой принцип действия?\r\n за что отвечает этот блок?\r\nИ этот узел. За чтототвечает, и что с ним делать	Динар Фасхутдинов	2026-02-04 11:06:16	30693	{"selectedItems":["58093_product"],"contactName":"\\u0420\\u0430\\u0441\\u0442\\u043e\\u0440\\u043e\\u043f\\u043e\\u0432 \\u0410\\u043d\\u0430\\u0442\\u043e\\u043b\\u0438\\u0439 \\u041f\\u0430\\u0432\\u043b\\u043e\\u0432\\u0438\\u0447","contactPhone":"79237778118","contactEmail":"kamin_22@mail.ru"}	\N	4	2026-02-05 08:01:11	ИП Расторопов Анатолий Павлович	completed	2026-02-05 06:26:50
33	Вопросы по станку	1. не работает сенсор экран\r\n2. не подпиливает меньше 1,5 мм.\r\n3. Не пилит пакетом больше 2х листов.\r\n4 Затаскивает детали на замер, но датчика измерения детали нет.	Дмитрий Мыслюк	2026-02-03 09:49:00	30793	{"selectedItems":["58343_product"],"contactName":"\\u041d\\u0438\\u043a\\u0438\\u0442\\u0430","contactPhone":"89196399426","contactEmail":""}	\N	4	2026-02-09 05:45:10	ООО "ТРАНСПРОЕКТИЗЫСКАНИЯ"	completed	2026-02-03 11:40:10
51	Не режет	Когда запускаем процесс резки . с азота на кислород переключает	Виктор Карасёв	2026-02-06 11:28:06	29638	{"selectedItems":["55168_product"],"contactName":"","contactPhone":"+79243729821","contactEmail":""}	\N	4	2026-02-06 12:04:59	ГАПОУ "КГПТ"	completed	2026-02-06 11:29:06
47	Неисправность трубореза	Ошибки на труборезе	Дмитрий Мыслюк	2026-02-06 06:13:24	25167	{"selectedItems":["46737_product"],"contactName":"\\u0421\\u0442\\u0435\\u0444\\u0430\\u043d","contactPhone":"+7 960 353-21-25","contactEmail":""}	\N	4	2026-02-09 05:06:00	ООО "ЭНГЕЛЬССКИЙ МЕТАЛЛ"	completed	2026-02-06 10:23:21
32	Вопросы по станку	1.При фрезеровки верхней фрезой прижим упирается в захват и выдает ошибку\r\n2.С периодичностью пропускает присадку отверстий не выдавая ошибок\r\n3.Каждый день сбиваются настройки (координаты) верхней головы\r\n4.износились прижимные колеса на верхней голове\r\n5.требуется настройка заднего выгружного стола \r\n6.Требуется наладка захватов (захватывает мимо детали)	Дмитрий Мыслюк	2026-02-03 09:48:03	30795	{"selectedItems":["58345_product"],"contactName":"\\u041d\\u0438\\u043a\\u0438\\u0442\\u0430","contactPhone":"89196399426","contactEmail":""}	\N	4	2026-02-09 05:55:10	ООО "ТРАНСПРОЕКТИЗЫСКАНИЯ"	completed	2026-02-09 05:55:02
55	Течет масло	Протекает масло , оборудование простаивает	Виктор Карасёв	2026-02-09 12:42:39	29711	{"selectedItems":["55322_product"],"contactName":"\\u0421\\u0435\\u0440\\u0433\\u0435\\u0439 \\u0422\\u044f\\u043f\\u043a\\u0438\\u043d","contactPhone":"+79119950910","contactEmail":""}	\N	4	2026-02-10 11:17:38	ООО "ЦИКЛ"	completed	2026-02-10 05:02:57
71	MetalTec HBС 50/1600 4+1 ЧПУ ESA630 Листогибочный пресс с ЧПУ	Вопрос по настройке станка для плющения металла.\r\nПо пониманию клиента, нужно лезть в доп настройки ЧПУ (но ЧПУ просит пароль).\r\nПлюс пароль клиент все равно хочет получить, независимо от текущего вопроса. Пароль для входа в настройки.	Данил Киряшин	2026-02-17 09:32:13	24262	{"selectedItems":["44700_product"],"contactName":"\\u0420\\u0430\\u0444\\u0430\\u044d\\u043b\\u044c","contactPhone":"+79520336692","contactEmail":""}	\N	4	2026-02-18 14:04:25	ООО "САЙН"	completed	2026-02-17 10:07:57
65	Запрос такелаж	Клиент интересуется, каким образом станок можно перемещать.	Дмитрий Мыслюк	2026-02-12 09:12:45	12317	{"selectedItems":["20150_product"],"contactName":"\\u041b\\u0435\\u043e\\u043d\\u0438\\u0434","contactPhone":"+79128295710","contactEmail":""}	\N	3	2026-02-12 09:30:51	ООО "ВТР-АВТО РУСС"	completed	2026-02-12 09:19:10
77	Прыгает лазерная голова	Прыгает лазерная голова.\r\nВидео https://cloud.mail.ru/stock/kT6ECicpZMXzut4mHwN42UhJ	Дмитрий Мыслюк	2026-02-19 13:28:41	27775	{"selectedItems":["51638_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0430\\u043d\\u0434\\u0440","contactPhone":"+79203679599","contactEmail":""}	\N	3	2026-02-24 12:04:18	ИП Юрмов Петр Михайлович	completed	2026-02-19 13:47:08
83	УДАЛИТЬ НАТСРЙОКИ	Помощь с программным обеспечением	Виктор Карасёв	2026-02-24 12:27:33	24491	{"selectedItems":["45193_product"],"contactName":"","contactPhone":"","contactEmail":""}	\N	4	2026-02-24 12:28:20	ООО "ЭНГЕЛЬССКИЙ МЕТАЛЛ"	completed	2026-02-24 12:28:13
82	удалить лишние настрйоки	удалить натсрйоки в хайпкате	Виктор Карасёв	2026-02-24 12:26:46	23887	{"selectedItems":["44030_product"],"contactName":"","contactPhone":"","contactEmail":""}	\N	4	2026-02-24 12:31:06	ООО "ЭНГЕЛЬССКИЙ МЕТАЛЛ"	completed	2026-02-24 12:28:38
84	Тестовая заявка — проверка создания	Создана автоматически для проверки работоспособности API после восстановления ВМ	Система (тест)	2026-03-04 09:58:41	99999	\N	\N	\N	\N	\N	new	\N
81	ООО "АМСТРОЙНЕФТЬ" поломка гильотины	Полетел трансформатор на гильотине. Запрашивают стоимость и наличие. Станок на гарантии, но клиент говорит, что это не гарантийный случай. Очень острый вопрос у них. Прошу связаться с ним.	Данил Киряшин	2026-02-24 09:16:56	30982	{"selectedItems":["58812_product"],"contactName":"\\u0410\\u0439\\u043d\\u0443\\u0440","contactPhone":"+79376166631","contactEmail":""}	\N	3	2026-03-04 13:49:02	ООО "АМСТРОЙНЕФТЬ"	completed	2026-02-24 13:24:04
85	Тест	Тестовая заявка	Сергей	2026-03-04 10:26:02	31605	{"selectedItems":["60642_product"],"contactName":"\\u0421\\u0435\\u0440\\u0433\\u0435\\u0439","contactPhone":"+79033149782","contactEmail":""}	\N	1	2026-04-09 06:17:21	ПАО "НПП "ИМПУЛЬС"	completed	2026-04-09 06:17:17
86	Не заускается шредер,перебой в сети	Не запускается шредер,перебой в сети.	Артур Ахметзянов	2026-03-04 11:39:38	30891	{"selectedItems":["58589_product"],"contactName":"\\u0412\\u0438\\u0442\\u0430\\u043b\\u0438\\u0439","contactPhone":"+79214444791","contactEmail":""}	\N	4	2026-03-05 06:17:56	ИП Габисов Герман Юрьевич	completed	2026-03-04 13:03:46
88	Код доступан к стойке ЧПУ Сайбилек	К нам обратился оператор листогибочного пресса. \r\n\r\nПросит предоставить код доступа к 4-му уровню стойки ЧПУ Cайбилек для просмотра моточасов. \r\nПлюс желает заменить задние упоры на прямоугольные. \r\n\r\nПоставщик ООО ПК Векпром \r\n\r\nКонтакты специалиста сервиса по листогибам\r\nРоман Егоров \r\n89255390424	Ильнур Сафин	2026-03-05 10:30:33	18387	{"selectedItems":["33170_product"],"contactName":"\\u041c\\u0430\\u043a\\u0441\\u0438\\u043c","contactPhone":"+79966347618","contactEmail":""}	\N	3	2026-03-06 08:52:01	ООО "МЕТАЛЛ ДИЗАЙН"	completed	2026-03-05 13:07:54
92	Кромкооблицовочный станок EB 295	кромка двойка детали больше метра в конце начинает рвать и не приклеивает	Динар Фасхутдинов	2026-03-23 06:23:00	30219	{"selectedItems":["57161_product"],"contactName":"\\u041c\\u0430\\u043a\\u0430\\u0440 \\u041c\\u0438\\u0445\\u0430\\u0438\\u043b \\u0412\\u0438\\u043a\\u0442\\u043e\\u0440\\u043e\\u0432\\u0438\\u0447","contactPhone":"79095586622","contactEmail":"miha_makar@mail.ru"}	\N	3	2026-03-23 09:54:41	ИП Макар Михаил Викторович	completed	2026-03-23 09:23:32
87	Проблема с лазерной головой	Дополнительное контактное лицо:\r\n+79514885899 Константин	Дмитрий Мыслюк	2026-03-05 10:06:30	17518	{"selectedItems":["31298_product"],"contactName":"\\u041c\\u0438\\u0445\\u0430\\u0438\\u043b","contactPhone":"+79681248122","contactEmail":""}	\N	3	2026-03-13 09:09:56	ООО "ЗЛАТОУСТОВСКИЙ КУЗНЕЧНО-ПРЕССОВЫЙ ЗАВОД"	completed	2026-03-05 10:56:24
89	Проблема с Листогибом	руководитель сообщает о недогибе при работе с алюминием, при гибе черного металла всё ок. Просит помощи, желательно выездом инженера. Так же проговорил о не прописанной матрице при покупке, но при этом говорит что всё ок, только на алюминии проблема. Просит срочно отреагировать.	Амир Мифтахов	2026-03-05 13:11:28	28555	{"selectedItems":["53027_product"],"contactName":"\\u0412\\u043b\\u0430\\u0434\\u0438\\u043c\\u0438\\u0440 \\u0421\\u043e\\u043a\\u043e\\u043b\\u043e\\u0432","contactPhone":"+79933352312","contactEmail":""}	\N	4	2026-03-11 08:34:22	АО "РАДИЙ ТН"	completed	2026-03-06 09:05:26
91	Нужна диагностика лазерного станка. ООО Доминант	Так же планируют попросить провести ТО станка	Равиль Камалутдинов	2026-03-12 14:18:50	12770	{"selectedItems":["21043_product"],"contactName":"\\u041c\\u0438\\u0445\\u0430\\u0438\\u043b","contactPhone":"79774859257","contactEmail":""}	\N	3	2026-03-17 12:35:26	ООО "ДОМИНАНТ"	completed	2026-03-13 12:24:07
93	Вопрос по компрессору	Клиент закупает у нас 2 трубореза Анжи 3 квт \r\nУ тех специалиста есть вопрос : хватит ли одного компрессора ВК20Т-16-500Д2 от Ремезы на одновременную работу 2х труборезов .	Николай Васильев	2026-03-23 10:11:22	31631	{"selectedItems":["60697_product"],"contactName":"\\u041c\\u0430\\u0440\\u0430\\u0442","contactPhone":"79263363800","contactEmail":""}	\N	3	2026-03-24 11:20:02	ООО "АЙКОВЕР ПРО"	completed	2026-03-23 13:10:44
94	Нужна помощь по настройке программы для лазерного станка JW 1616 с конвейером	Со слов Рустама, вроде как не получается настроить бесконечную протяжку.\r\nСкорее необходимо будет обратиться на завод за поддержкой.	Артём Третьяков	2026-03-24 08:34:26	31510	{"selectedItems":["60372_product"],"contactName":"\\u0413\\u0440\\u0438\\u0433\\u043e\\u0440\\u0438\\u0439","contactPhone":"89196963999","contactEmail":""}	\N	4	2026-03-24 09:00:44	ООО "ТПК ЭКСИ-ДИЗАЙН"	completed	2026-03-24 09:00:31
95	Настройки	Клиент хочет из профильной трубы вырезать одну грань, но чет не получается у него	Николай Васильев	2026-03-25 11:14:02	31137	{"selectedItems":["59388_product"],"contactName":"\\u0421\\u0435\\u0440\\u0433\\u0435\\u0439","contactPhone":"+7 910 259-02-84","contactEmail":""}	\N	3	2026-03-26 07:15:09	ООО "ПРИЦЕПЦЕНТР"	completed	2026-03-25 12:26:23
98	Вопрос по эксплуатации измельчителя DWG60	Не может настроить систему no-stress\r\nПо инструкции залил гидравлическое масло и после это перестали запускаться валы подачи	Олег Селезнев	2026-03-27 09:38:48	31012	{"selectedItems":["58886_product"],"contactName":"\\u0421\\u0442\\u0430\\u043d\\u0438\\u0441\\u043b\\u0430\\u0432","contactPhone":"89212103945","contactEmail":""}	\N	4	2026-03-31 08:26:02	Линьков Станислав Геннадьевич	completed	2026-03-31 08:25:38
96	Подключение станка	Вопрос по подключению станка (паспорта нет)	Дмитрий Мыслюк	2026-03-25 13:33:04	31681	{"selectedItems":["60928_product"],"contactName":"\\u041c\\u0430\\u043a\\u0441\\u0438\\u043c","contactPhone":"+79219948014","contactEmail":""}	\N	4	2026-04-09 06:40:47	ИП Розов Максим Юрьевич	completed	2026-03-26 07:22:42
90	ывфафыва	фывафываф	Артур Зиннуров	2026-03-11 12:21:06	31643	{"selectedItems":["60720_product"],"contactName":"\\u044f\\u0447\\u0441\\u043c\\u0447\\u0441\\u043c\\u044f\\u0447","contactPhone":"23432534","contactEmail":""}	\N	1	2026-04-09 06:17:05	ИП Киреева Татьяна Владимировна	completed	2026-04-09 06:16:57
100	Просит тех. поддержку по запуску станков	ИП Евстафьев Евгений Владимирович\r\nПросит тех. поддержку по запуску станков .Купил у нас станки.\r\nКонтактное лицо :Евгений,г.Великий Новгород	Али Шарафутдинов	2026-03-31 13:17:22	31298	{"selectedItems":["59749_product"],"contactName":"\\u0415\\u0432\\u0433\\u0435\\u043d\\u0438\\u0439","contactPhone":"8952 480-01-58","contactEmail":""}	\N	4	2026-04-01 07:13:59	ИП Евстафьев Евгений Владимирович	completed	2026-04-01 07:13:44
99	Не меняются настройки лазерного станка  Спецприцеп	Не меняются настройки для резки на лазерном станке	Равиль Камалутдинов	2026-03-30 10:32:12	22515	{"selectedItems":["41405_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0430\\u043d\\u0434\\u0440","contactPhone":"+7 963 154-20-12","contactEmail":""}	\N	4	2026-04-01 07:22:57	ООО "КОМПАНИЯ "СПЕЦПРИЦЕП"	completed	2026-03-31 05:26:03
97	Эксплуатация лазерного станка и чиллера	Клиент говорит, что у чиллера температура воды поднялась до 24 градусов и не опускается, они боятся дальше работать.\r\nНасколько знаю 24 градуса - это рабочая температура. Необходимо успокоить клиента и предоставить ему информацию по эксплуатации станка и чиллера.	Артём Третьяков	2026-03-26 11:49:03	30336	{"selectedItems":["57385_product"],"contactName":"\\u0414\\u043c\\u0438\\u0442\\u0440\\u0438\\u0439","contactPhone":"+7 982 836-33-25","contactEmail":""}	\N	4	2026-04-01 07:05:49	ООО "АЭРОСКАН"	completed	2026-03-31 05:28:29
106	вопрос по передней бабке	Наблюдается не стабильная работа станка	Дмитрий Мыслюк	2026-04-08 11:16:04	12485	{"selectedItems":["20826_product"],"contactName":"\\u0422\\u0440\\u043e\\u0444\\u0438\\u043c","contactPhone":"89384300957","contactEmail":""}	\N	3	2026-04-13 06:50:16	Звездова Светлана Александровна	completed	2026-04-08 11:31:08
103	PBS 135 3200 D53	Клиент купил пуансон "гусь". Нужна помощь в программировании! \r\n\r\nВопрос от клиента:\r\nПокупали у вас процесс в том году листогибочный на стойке Delem Da-53T подскажите там функции загрузки нового инструмента через Dxf формат с флэшки отсутствует ?	Ильнур Сафин	2026-04-06 11:10:37	28574	{"selectedItems":["53053_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0430\\u043d\\u0434\\u0440 \\u0412\\u0438\\u043a\\u0442\\u043e\\u0440\\u043e\\u0432\\u0438\\u0447","contactPhone":"+79201112225","contactEmail":""}	\N	3	2026-04-07 07:03:24	ООО "ПРОМЕТ-НН"	completed	2026-04-06 14:30:16
102	На станке выходи ошибка	На станке ошибка, нужна помошь в решении. ранее ошибка сама пропадала, теперь не пропадает	Татьяна Салахудинова	2026-04-06 05:27:08	19111	{"selectedItems":["34614_product"],"contactName":"\\u0420\\u0443\\u0441\\u0442\\u0430\\u043c  \\u0421\\u0438\\u0442\\u0434\\u0438\\u043a\\u043e\\u0432","contactPhone":"+7 912 875-09-29","contactEmail":""}	\N	3	2026-04-07 08:37:18	ООО "ДОКА-КЛИМАТ"	completed	2026-04-06 08:08:50
105	Просит тех .поддержку	Вы можете спросить у тех. отдела) Возник один вопрос п о станку сверлильно пазовальный мод.MS3112, можете нам помочь: ест время цикла, мы его задаём но при нажатии кнопки enter не подтверждает. На видео должно всё быть видно.\r\nконтактное лицо Евгений	Али Шарафутдинов	2026-04-07 12:18:05	31298	{"selectedItems":["59750_product"],"contactName":"\\u0418\\u041f \\u0415\\u0432\\u0441\\u0442\\u0430\\u0444\\u044c\\u0435\\u0432","contactPhone":"7 952 480-01-58","contactEmail":""}	\N	3	2026-04-13 11:53:39	ИП Евстафьев Евгений Владимирович	completed	2026-04-08 07:40:33
107	Требуется техническое обслуживание станков	Фрезерные станки с ЧПУ по дереву, покупали у нас.	Артём Третьяков	2026-04-09 06:08:40	23815	{"selectedItems":["43867_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0430\\u043d\\u0434\\u0440","contactPhone":"89991696602","contactEmail":""}	\N	1	2026-04-09 06:16:46	ООО "НЛТ"	completed	2026-04-09 06:16:33
104	Вопросы по работе лазерного станка LM 2030 300Вт	Ошибки по лазеру\r\n\r\n1) Расхождение диагонали на 2мм.\r\n\r\n2) Левый край стола. Не вырезает окружность.\r\n\r\n3) Ребристые края деталей + ребристость при резке по диагонали \r\n\r\nВопрос по лазеру\r\n\r\n1) Чистка и замена линзы.	Олег Селезнев	2026-04-06 14:17:56	30769	{"selectedItems":["58313_product"],"contactName":"\\u0412\\u043b\\u0430\\u0434\\u0438\\u043c\\u0438\\u0440","contactPhone":"7 967 532-65-35","contactEmail":""}	\N	4	2026-04-09 06:36:07	ООО "РЕКЛАМНЫЕ КОНСТРУКЦИИ"	completed	2026-04-06 14:43:01
108	ООО "ВАННВАНЫЧ"	Загрязнение защитки со стороны головы	Виктор Карасёв	2026-04-09 12:53:03	30781	{"selectedItems":["58330_product"],"contactName":"\\u0420\\u043e\\u043c\\u0430\\u043d","contactPhone":"","contactEmail":""}	\N	4	2026-04-10 12:25:04	ООО "ВАННВАНЫЧ"	completed	2026-04-09 12:54:09
101	Не зажимают кулачки	При зажиме заготовки задней бабки Y3 кулачки плохо зажимают заготовку .	Виктор Карасёв	2026-04-03 11:02:01	25167	{"selectedItems":["46737_product"],"contactName":"\\u0421\\u0442\\u0435\\u0444\\u0430\\u043d","contactPhone":"+79603532125","contactEmail":""}	\N	4	2026-04-10 06:04:45	ООО "ЭНГЕЛЬССКИЙ МЕТАЛЛ"	completed	2026-04-03 11:02:35
110	Травит воздух	Пропускает цилиндр(утечка)	Сергей Маврин	2026-04-14 07:42:12	26624	{"selectedItems":["49388_product"],"contactName":"\\u0414\\u0430\\u043d\\u0438\\u0438\\u043b \\u0421\\u043c\\u0438\\u0440\\u043d\\u043e\\u0432","contactPhone":"+79818868406","contactEmail":"pro@artikul-mebel.ru"}	\N	3	2026-04-14 07:47:10	ООО "ПК "АРТИКУЛ-МЕБЕЛЬ"	completed	2026-04-14 07:46:25
113	Нужен пароль	После проведения ремонта когда клиенту понадобилось переключить настройку с чистки на сварку . оборудование затребовало пароль . пароль которые мы им предоставили не подходит	Виктор Карасёв	2026-04-21 06:01:39	25531	{"selectedItems":["47425_product"],"contactName":"\\u0410\\u043d\\u0430\\u0442\\u043e\\u043b\\u0438\\u0439","contactPhone":"79171202561","contactEmail":""}	\N	4	2026-04-21 13:05:19	ПАО "АЗОТРЕММАШ"	completed	2026-04-21 06:02:22
109	Форматнок раскроечный W9D	Динар, добрый вечер, появился шум и вибрация по станку при включении узла подрезки, ремень там как указано в паспорте регулируется автоматически. Грешим на подшипники, что делать в таком случае? При получении станка еще был обнаружен дефект по ржавчине основного стола(фото во вложении), возможно и в подшипниках образовалась при хранении ржавчина и сейчас такая неисправность случилась.\r\nСказали был бы паз как на фото, можно было бы поправить раздвинув с одной стороны\r\nНаладчик был наш, но подрезка никак не настраивается, там подшипники не обслуживаемые, а ремень с автоматической натяжкой.	Динар Фасхутдинов	2026-04-13 06:01:03	31400	{"selectedItems":["60094_product"],"contactName":"\\u041a\\u0430\\u0440\\u0442\\u0430\\u0432\\u044b\\u0445 \\u041e\\u043b\\u0435\\u0433 \\u0410\\u043b\\u0435\\u043a\\u0441\\u0430\\u043d\\u0434\\u0440\\u043e\\u0432\\u0438\\u0447","contactPhone":"79177019142","contactEmail":"olegkart333@yandex.ru"}	\N	4	2026-04-14 13:34:35	Картавых Олег Александрович	completed	2026-04-13 07:35:40
112	Ошибка оси Z	Уходит в ошиьку время от времени по Z	Виктор Карасёв	2026-04-16 07:39:16	10613	{"selectedItems":["16927_product"],"contactName":"\\u0410\\u043b\\u0435\\u043a\\u0441\\u0430\\u043d\\u0434\\u0440","contactPhone":"79055184486","contactEmail":""}	\N	4	2026-04-17 07:56:35	ООО "ТЕХСТРОЙМОНТАЖ"	completed	2026-04-16 07:40:21
114	Не хватате звездочки на станке  Комбинированный деревообрабатывающий станок MQ3435	Прошу связаться с клиентом, получить информацию. \r\nЕсть подозрения что не правильно делают наладку. \r\nЕсли Все таки звездочки не хватает то рекламация.	Азат Гатауллин	2026-04-21 07:37:36	31827	{"selectedItems":["61298_product"],"contactName":"\\u041c\\u0430\\u0440\\u0438\\u044f","contactPhone":"79297312111","contactEmail":"m.nic.com@yandex.ru"}	\N	4	2026-04-21 09:20:23	ИП Менкнасунова Мария Батыровна	completed	2026-04-21 09:20:17
111	Траверса не идёт вниз	Траверса доходит до места нахождения материала и ниже не опускается	Виктор Карасёв	2026-04-14 12:52:31	15838	{"selectedItems":["27600_product"],"contactName":"\\u0421\\u0430\\u043b\\u043c\\u0430\\u043d","contactPhone":"","contactEmail":""}	\N	4	2026-04-21 09:21:35	ООО "ТД ПЯТОВСКОЕ КАРЬЕРОУПРАВЛЕНИЕ"	completed	2026-04-14 14:18:28
116	ООО "КУЗНЕЧНЫЙ ЦЕХ" // LD-3015S — пропал красный луч	Проблема с оборудованием - красный луч пропал на голове	Никита Серов	2026-04-27 07:50:00	30957	{"selectedItems":["58748_product"],"contactName":"","contactPhone":"79872907792","contactEmail":"2907792@mail.ru"}	\N	4	2026-04-27 11:10:23	ООО "КУЗНЕЧНЫЙ ЦЕХ"	completed	2026-04-27 11:09:06
115	Компания Домиминат	Доброе утро, подскажите что за ошибка на листогибе Ph0067 \r\nЛистогиб PBA от Ками групп.	Равиль Камалутдинов	2026-04-27 05:47:49	16727	{"selectedItems":["29533_product"],"contactName":"\\u041c\\u0438\\u0445\\u0430\\u0438\\u043b","contactPhone":"79774859257","contactEmail":""}	\N	3	2026-04-27 08:25:39	ООО "ДОМИНАНТ"	completed	2026-04-27 08:24:01
\.


--
-- Data for Name: support_ticket_comment; Type: TABLE DATA; Schema: public; Owner: -
--

COPY "public"."support_ticket_comment" ("id", "comment", "closing_reason", "status", "created_at", "support_ticket_id", "user_id") FROM stdin;
1	Обновлено программное обеспечение ЧПУ. Установлены последние версии драйверов. Обновлено программное обеспечение ЧПУ. Установлены последние версии драйверов. Обновлено программное обеспечение ЧПУ. Установлены последние версии драйверов.	\N	in_progress	2026-01-20 18:14:12	1	1
2	Проведена проверка точности позиционирования. Все параметры в допустимых пределах.	\N	postponed	2026-01-20 18:14:24	1	1
3	Заменены изношенные режущие инструменты. Проводим пробную обработку материала. Заменены изношенные режущие инструменты. Проводим пробную обработку материала.	\N	postponed	2026-01-20 18:14:34	1	1
4	Рекомендуется регулярное техническое обслуживание для предотвращения подобных ситуаций. 	\N	in_progress	2026-01-20 18:14:54	1	1
5	Документация по выполненным работам передана заказчику. Документация по выполненным работам передана заказчику.	transferred_to_claims	completed	2026-01-20 18:15:10	1	1
6	Обновлено программное обеспечение ЧПУ. Установлены последние версии драйверов.	\N	in_progress	2026-01-21 06:36:46	3	3
7	Станок запущен в тестовом режиме. Все системы функционируют нормально.	\N	in_progress	2026-01-21 06:36:55	3	3
8	'Заменены поврежденные комплектующие. Проводим тестирование работы станка.	\N	postponed	2026-01-21 06:37:02	3	3
9	Настроена система безопасности. Все аварийные датчики функционируют корректно.	\N	in_progress	2026-01-21 06:37:30	2	4
10	Документация по выполненным работам передана заказчику.	\N	in_progress	2026-01-21 06:37:37	2	4
11	Рекомендуется регулярное техническое обслуживание для предотвращения подобных ситуаций.	\N	postponed	2026-01-21 06:37:46	2	4
12	Заявка выполнена успешно. Рекомендуем провести плановое обслуживание через 6 месяцев.	\N	in_progress	2026-01-21 06:37:55	2	4
13	Проверка смены статуса	\N	in_progress	2026-01-22 09:56:36	4	1
14	пропала область добавления файлов	\N	in_progress	2026-01-23 10:48:25	4	1
15	В информации о заказе ошибка загрузки данных	\N	in_progress	2026-01-23 10:48:53	4	1
16	Проведена проверка точности позиционирования. Все параметры в допустимых пределах.	\N	in_progress	2026-01-23 11:06:11	4	1
17		\N	in_progress	2026-01-23 13:08:05	5	1
18	отложено	\N	postponed	2026-01-23 13:16:30	5	1
19	решено 	resolved	completed	2026-01-26 06:33:35	2	4
20	завершено	resolved	completed	2026-01-26 06:33:49	3	4
21	завершено	\N	in_progress	2026-01-26 06:34:11	6	4
22	завершено	resolved	completed	2026-01-26 06:34:21	6	4
23	С клиентом на связи ожидаю звонка оператора	\N	in_progress	2026-01-26 06:34:40	7	4
24	отложено 	\N	postponed	2026-01-26 07:07:10	7	4
25	Клиенту сейчас позвоню, запрошу фото, видео.	\N	in_progress	2026-01-26 07:09:31	8	3
26		\N	in_progress	2026-01-26 08:06:07	8	3
27	в работе\n	\N	in_progress	2026-01-26 08:07:04	7	4
28	В ходе тех.поддержки  . проверили защитные стекла . попробовали перевернуть по рекомендации тех.поддержки ХТ . не помогает , предложили обновить программное обеспечение.	\N	in_progress	2026-01-26 08:14:28	7	4
29	Оставил заявку в Интервест, с сервисным отделом связаться не смогли, обещали перезвонить 	\N	in_progress	2026-01-26 08:21:32	8	3
30	Завод прислал дистрибутив новый , установим поверх старого проверим ушла ли ошибка\n	\N	in_progress	2026-01-26 08:33:35	7	4
31	Ошибку устранили , оборудование работает в штатном режиме.	resolved	completed	2026-01-26 08:51:51	7	4
32	С клиентом на связи , ожидаю информацию	\N	in_progress	2026-01-26 09:04:07	10	4
33	Жду звонка с Интервеста.	\N	in_progress	2026-01-26 09:07:52	8	3
34	Жду звонка с Интервеста.	\N	postponed	2026-01-26 09:08:06	8	3
35	Буду звонить клиенту, запрошу информацию.	\N	in_progress	2026-01-26 09:08:34	9	3
36	Двигатель по Y гудит, время от времени.не постоянно. Гарантия закончилась. нужен выезд на платной основе.	transferred_to_claims	completed	2026-01-26 09:48:50	9	3
37	Клиенту дал рекомендации по выставлению заднего захвата.	\N	in_progress	2026-01-26 10:33:23	8	3
38	Техподдержка Интервеста сказала что по договору, они нам выставояют счёт, мы его оплачиваем и они едут чинить, поэтому помощи от них не будет.	\N	in_progress	2026-01-26 10:35:33	8	3
39	Диагональ поправили	\N	in_progress	2026-01-26 11:28:12	8	3
40	Клиент говорит, что после выходных ушла диагональ и вместо 5 листов раскладывает только 2 листа. 	\N	in_progress	2026-01-26 11:38:07	8	3
41	Рекомендации предоставил , завтра проверит . Если не поможет рекомендовал ему выезд сервисного специалиста . Для настройки серводрайвера. 	\N	in_progress	2026-01-26 11:44:08	10	4
42	Отложили до завтра .	\N	postponed	2026-01-26 11:44:25	10	4
43	В работе жду , информацию.	\N	in_progress	2026-01-26 11:54:56	11	4
44	Диагональ выставили, всё ровно. 	resolved	completed	2026-01-26 12:40:40	8	3
45	Решили сделать выезд клиента.  	transferred_to_service	completed	2026-01-27 06:18:13	10	4
46	С клиентом созвонился, с его слов везде 90 градусов выставлено. Материал храниться при температуре -10 градусов, пришли к выводу что из за перепада температур материал играет. Клиент сказал, что когда потеплеет(весной) будет работать и сообщит остался банан или нет. \n\nСайт висел 2 часа, не мог зайти.	\N	in_progress	2026-01-27 09:40:51	12	3
47	с клиентом созвонимся весной. Всё должно быть в порядке.	resolved	completed	2026-01-27 09:41:29	12	3
48	Ожидаю информацию от клиента , как скинет возобновлю	\N	postponed	2026-01-27 13:35:45	11	4
49	Абонент Николай не берёт трубку.	\N	in_progress	2026-01-28 06:21:43	13	3
50	Буду звонить дальше	\N	postponed	2026-01-28 06:22:13	13	3
51	У клиента был вопрос по поводу прифуговочного узла. Он должен быть зафиксированным.Зафиксировали, работают.	resolved	completed	2026-01-28 08:45:52	13	3
52	С клиентом связался Запросил информацию , запросил фото щитка и концевиков . Вероятная причина неисправности могло подгореть реле , может пускатель не даёт питание на двигатель , думаю что механика впорядке потому что в ручном режиме все работает без нареканий. 	\N	in_progress	2026-01-28 09:08:05	14	4
53	Ожидаю от клиента информацию	\N	postponed	2026-01-28 10:53:38	14	4
54	В работе , при нажатии на педаль ничего не происходит 	\N	in_progress	2026-01-28 10:58:44	15	4
55	Сейчас буду связываться с клиентом.	\N	in_progress	2026-01-28 12:42:43	16	3
56	В результате осмотра оборудования   было обнаружено что один из серводрайверов в ошибке Z2 ошибка FLT /  при отключении серводрайвера  оборудование работает . как временную меру мы отключаем программно эти оси . Далее для устранения неисправности планирую связаться с заводом \n	\N	in_progress	2026-01-28 13:20:28	15	4
57	Клиенту отправил фото, что куда подключается. Так же подсказал какого сечения кабель использовать.	resolved	completed	2026-01-28 13:21:46	16	3
58	Клиент сказал что нет доступа к щитку , как появится доступ скинут фотки.	\N	postponed	2026-01-28 13:58:21	14	4
59	перенесли на завтра.	\N	in_progress	2026-01-28 13:58:35	15	4
60	перенесли на завтра\n	\N	postponed	2026-01-28 13:58:47	15	4
61	Направили клиенту письмо с разрешением на демонтаж защитного кожуха , что бы проверить осмотреть узел ременного соединения отвечающего за быстрое перемещение . позиция 392 на взрыв схеме	\N	in_progress	2026-01-29 07:33:00	14	4
62	Зубья стерлись , передал в рекламации	transferred_to_claims	completed	2026-01-29 07:56:27	14	4
63	С клиентом связался, запросил фото.	\N	in_progress	2026-01-29 08:58:03	17	3
64	Люфт был на 2-х двигателях по Y. Произвели диагностику и протяжку двигателей. на фото Без имени11 рез, после протяжки.	resolved	completed	2026-01-29 09:00:00	17	3
65	Запрос фото от клиента.	\N	in_progress	2026-01-29 09:09:24	18	3
66	Удалённо подключались с китайцами, устранили ошибку оси V.	resolved	completed	2026-01-29 09:13:22	18	3
67	рекомендации предоставил . надо скинуть мотор и погонять в холостую что бы проверить его работоспособность  без нагрузки. Относительно интсрумента . Сгибают 30 градусов ровно но когда ставят в зажим , его выталкивает . вероятно из за неверной программы т. слишком большая скорость .	\N	in_progress	2026-01-29 10:56:28	15	4
68	на связь не выходит , игнорирует звонки	\N	postponed	2026-01-29 10:58:39	11	4
69	матрица и пуансон введены в базу инструментов.	resolved	completed	2026-01-30 05:02:10	15	4
70	В работе 	\N	in_progress	2026-01-30 06:05:20	19	4
71	Позвоню клиенту и подскажу.	\N	in_progress	2026-01-30 06:21:36	20	3
72	Не отвечает клиент	\N	postponed	2026-01-30 06:25:54	20	3
73	на подающем ролике имеется отклонение реальной поверхности вращающегося ролика  от её идеальной цилиндрической формы  и соосности с осью вращения. 	\N	in_progress	2026-01-30 06:32:21	19	4
74	Переданно в рекламации	transferred_to_claims	completed	2026-01-30 06:40:14	19	4
75	Клиент сказал вечером попробует, ждём.	\N	postponed	2026-01-30 06:43:14	20	3
76	В работе	\N	in_progress	2026-01-30 08:26:34	21	4
77	Рекомендации предоставил , как сделает . и выйдет на связь возобновлю 	\N	postponed	2026-01-30 11:12:24	21	4
78	Позвоню клиенту и запрошу информацию.	\N	in_progress	2026-01-30 11:32:57	23	3
79	 Vb	\N	in_progress	2026-01-30 12:07:37	22	1
80	Всё сделали	resolved	completed	2026-01-30 12:11:25	22	1
81	Приняло в работу.	\N	in_progress	2026-01-30 12:31:33	24	1
82	Отлично!	resolved	completed	2026-01-30 12:31:40	24	1
83	Позвонил?	\N	in_progress	2026-01-30 12:49:01	23	1
84	Какие рекомендации предоставил? Важно зафиксировать их здесь в системе! \n\nРуслан. 	\N	postponed	2026-01-30 12:51:36	21	1
85	Напиши пожалуйста что рекомендовал	\N	postponed	2026-01-30 12:52:21	21	1
86	Клиент подсоединил разьём, свет заработал.	resolved	completed	2026-01-30 13:52:49	20	3
249	ожидаю информацию как скинут передам в рекламационный отдел	transferred_to_claims	completed	2026-02-16 07:08:47	61	4
87	На валу появляется чернота, за 2 дня цвет клея стал техническим, серым, со слов клиента. В понедельник буду писать китайцам.	\N	in_progress	2026-01-30 14:00:28	23	3
88	Жду ответа клиента	\N	postponed	2026-02-02 05:53:46	23	3
89	Позвонил клиенту	\N	in_progress	2026-02-02 08:24:54	25	3
90	Не взял трубку	\N	postponed	2026-02-02 08:25:02	25	3
91	Буду звонить узнавать	\N	in_progress	2026-02-02 08:27:43	26	3
92	Mobil Vactra Oil No. 2 рекомендовал клиенту данное масло, так же скинул руководство по эксплуатации.	resolved	completed	2026-02-02 08:38:33	26	3
93	Жду доп фото от клиента.	\N	in_progress	2026-02-02 08:39:00	25	3
94	жду фото очищенной ванны	\N	postponed	2026-02-02 09:29:03	23	3
95	Принято в работу	\N	in_progress	2026-02-02 10:56:54	27	4
96	С клиентом связались , они сами купят подшипник , и заменят . Вопрос снят.	resolved	completed	2026-02-02 10:57:23	27	4
97	Клиент пропал, фото не присылает	\N	postponed	2026-02-02 11:24:35	25	3
98	До сих пор ничего не скинул, ему писал, не отвечает	\N	postponed	2026-02-02 13:21:38	25	3
99	Клиент обратился сразу по телефону.	\N	in_progress	2026-02-02 14:29:47	28	3
100	Восстановили работоспособность программы.	resolved	completed	2026-02-02 14:30:18	28	3
101	Обратился к китайцам , ожидаю ответ . 	\N	in_progress	2026-02-03 05:20:08	29	4
102	принимаю другую задачу	\N	postponed	2026-02-03 06:24:37	29	4
103	При запуске гибочного станка , он должен откалиброваться , в момент калибровки когда отьезжает назад больше ничего не происходит он останавливается и не движется . серводрайверы в ошибках .	\N	in_progress	2026-02-03 06:25:17	30	4
104	Неисправность такого рода может возникать в результате не работающего датчика . Когда происходит процесс калибровки оборудование не видит датчик "он не срабатывает" , и оборудование уходит в ошибку . Рекомендовал клиенту осмотреть датчики , срабатывают ли они , так как там установленные индуктивные датчики рекомендовал поднести металлическую пластинку обычно красным загорается .  рекомендовал осмотреть на предмет обрыва и на предмет загрязнения. 	\N	in_progress	2026-02-03 06:51:36	30	4
105	датчик не реагирует  на металл , контактные соединения впорядке 	\N	in_progress	2026-02-03 07:03:12	30	4
106	принял другую задачу	\N	postponed	2026-02-03 07:53:28	30	4
107	После обновления программного обеспечения , перестали нажиматься кнопки . ну нажимаешь но ничего не происходит . Запросил фото шильдика фото ПО . обращусь к китайцам	\N	in_progress	2026-02-03 08:09:04	31	4
108	Клиент сказал, что клей еще не выработал, как закончит скинет фото	\N	postponed	2026-02-03 08:33:43	23	3
109	Клиент сам в вичате с китайцами решает проблему	resolved	completed	2026-02-03 08:58:14	30	4
110	В ходе оказания технической поддержки , мы сделали следующие :  сняли двигатель , запустили без нагрузки , всё равно уходил в ошибку , это исключает механическую проблему косозубой рейки и натяжения ремня и привода редуктора . следовательно  проблема в энкодере или в кабеле питания . снимать кабели питания клиенты не стали . ими было принято решение купить новый двигатель .	resolved	completed	2026-02-03 09:02:36	21	4
111	Перенакатили бекап параметров . всё работает . Обновлять ненужно .	resolved	completed	2026-02-03 09:31:49	31	4
112	Ошибочно создана.	\N	in_progress	2026-02-03 11:02:55	35	1
113	Ошибочно создана.	resolved	completed	2026-02-03 11:03:03	35	1
114	В работу принял разбираюсь . 	\N	in_progress	2026-02-03 11:40:10	33	4
115	Сейчас позвоню клиенту.	\N	in_progress	2026-02-03 12:10:17	37	3
116	Ответил на вопросы клиента, руководство скинул, сюда не загружается.	resolved	completed	2026-02-03 12:13:19	37	3
117	Буду писать на завод.	\N	in_progress	2026-02-03 12:14:19	36	3
118	Написал китайцам, жду ответ.	\N	postponed	2026-02-03 12:44:35	36	3
119	Буду звонить клиенту, запрашивать информацию.	\N	in_progress	2026-02-03 12:45:24	34	3
120	Завтра клиент пришлёт все фото.	\N	postponed	2026-02-03 13:06:45	34	3
121	От китайцев ответ не дождался , ошибка "z axis encoder has no response "  нет обратной связи от энкодера . Протянули контактные соединения , вероятно сигнал плохо проходил . сегодня при первом запуске небыло ошибки .  	resolved	completed	2026-02-04 05:11:48	29	4
122	Буду звонить клиенту, соберу информацию.	\N	in_progress	2026-02-04 06:21:26	39	3
123	Направил Дмитрию информацию, по строповки и перевозке станка.	resolved	completed	2026-02-04 06:53:08	36	3
124	С клиентом созвонился, обьяснил, что предупреждение выходит о необходимости ТО.	resolved	completed	2026-02-04 07:36:33	39	3
125	1 .для того что бы работал сенсорный экран , эту функцию необходимо включить в настройках виндовс . если нет драйвера то (который ненужен) необходимо скачать их открытого доступа .	\N	in_progress	2026-02-04 08:08:15	33	4
126	Буду звонить клиенту, запрашивать информацию.	\N	in_progress	2026-02-04 08:08:55	40	3
127	по остальном вопросам запросил информацию у клиента в видео формате , что бы точно понимать что хочет клиент . 	\N	in_progress	2026-02-04 08:09:10	33	4
128	Жду фото от клиента.	\N	postponed	2026-02-04 08:12:28	40	3
129	Клиент не прислал до сих пор фото	\N	postponed	2026-02-04 08:13:23	23	3
130	Телефон абонента отключен.	\N	postponed	2026-02-04 09:12:12	34	3
131	буду звонить клиенту.	\N	in_progress	2026-02-04 09:13:13	38	3
132	В торцовочном узле было выставлено некорректное давление, рекомендовал вывставить 0,2-0,3 МПа. Будет пробовать. Станку 3 года, пуско-наладку производил сам клиент. 	resolved	completed	2026-02-04 09:18:54	38	3
133	принял другую	\N	postponed	2026-02-04 10:10:26	33	4
134	Artem Rakhimov\n79374718305   контакт для связи .\nс клиентом связался ,запросил дополнительную информацию . 	\N	in_progress	2026-02-04 10:11:11	41	4
135	абонент не абонент	\N	postponed	2026-02-04 10:21:04	34	3
136	клиент не берёт трубку\n	\N	postponed	2026-02-04 11:50:05	34	3
137	Созвонюсь с клиентом	\N	in_progress	2026-02-04 12:51:29	44	3
138	у клиента есть в наличии лампа RECI 130-150 W. Ему сказал, что могут поменять сами, ответственность за замену, тоже будут нести они. Старая лампа под замену.	resolved	completed	2026-02-04 13:52:15	40	3
139	Клиент оплатил счёт, завтра будут забирать.	resolved	completed	2026-02-04 14:00:42	44	3
140	Клиент ничего не прислал.	\N	postponed	2026-02-04 14:03:08	23	3
141	Клиент 5 дней не может скинуть фото. Заявку закрываю. При повторном обращении, создам новую.	resolved	completed	2026-02-04 14:14:44	23	3
142	сегодня еще обнаружили подтёк	\N	in_progress	2026-02-05 04:53:56	41	4
143	По первой протечке :  этот узел обычно называется муфтой   или соединением двигателя с винтовым блоком.  Возможные причины утечки масла в этой области\nСогласно руководству по эксплуатации утечка масла может быть вызвана:\nИзнос или повреждение уплотнений (сальников) в месте соединения двигателя с винтовым блоком.\nОслабление крепежа болтов или соединений.\nПовреждение масляных трубок или соединений вблизи этого узла.	\N	in_progress	2026-02-05 05:00:34	41	4
144	Второе место протечки синхронный двигатель с постоянными магнитами с масляным охлаждением .	\N	in_progress	2026-02-05 05:17:11	41	4
145	Переданно в рекламации , по причине того что оборудование гарантийное , вскрывать самому не рекомендуется .	transferred_to_claims	completed	2026-02-05 05:24:39	41	4
146	Создали 2 ор ошибке	\N	in_progress	2026-02-05 06:26:20	42	4
147	по ошибке	resolved	completed	2026-02-05 06:26:30	42	4
148	Принял в работу 	\N	in_progress	2026-02-05 06:26:50	43	4
149	Жду фото клиента	\N	postponed	2026-02-05 07:48:43	34	3
150	Первое фото червячный редуктор . Это регулятор скорости. Скорость можно регулировать только во время работы станка. Эксплуатация регулятора при остановленном станке приведет к изгибанию и деформации внутреннего винта.	\N	in_progress	2026-02-05 07:57:33	43	4
151	второе фото . Не должно крепится , когда конвейерная лента оказывает давление на нейлоновое колесо, она сжимает внутренний воздушный клапан, который, в свою очередь, приводит в действие цилиндр, заставляя его растягивать и натягивать конвейерную ленту, втягивая ее дальше внутрь.	\N	in_progress	2026-02-05 07:58:02	43	4
152	3 фото . Это блок подготовки воздуха . с влага отделителем и ёмкость для лубрикаторного масла . Это и под воздух и под масло .	\N	in_progress	2026-02-05 07:59:52	43	4
153	4 фото , шлейф выпал надо вставить обратно .	\N	in_progress	2026-02-05 08:00:15	43	4
154	5 фото . Да питание сюда. 	\N	in_progress	2026-02-05 08:00:27	43	4
155	Информация донесена клиенту в полном объёме .  Вопрос закрыт .	resolved	completed	2026-02-05 08:01:11	43	4
156	нечаянно создал	\N	in_progress	2026-02-05 08:20:31	45	4
157	ошибочно 	resolved	completed	2026-02-05 08:20:40	45	4
158	Источник в ошибке 	\N	in_progress	2026-02-05 08:23:13	46	4
159	тест завершён	resolved	completed	2026-02-05 11:28:24	5	3
160	Ошибку сняли , нужен был код регистрации	resolved	completed	2026-02-05 11:41:06	46	4
161	Жду фото от клиента, очень долго отвечает.	\N	postponed	2026-02-05 12:47:33	34	3
162	Буду звонить запрашивать информацию.	\N	in_progress	2026-02-06 06:19:35	48	3
163	жду ответа клиента.	\N	postponed	2026-02-06 07:57:42	48	3
164	Жду фото, с клиентом созвонился.	\N	in_progress	2026-02-06 07:58:16	49	3
165	8(910)7454305 Оператор Денис	\N	in_progress	2026-02-06 08:05:28	49	1
166	Какие новости тут?	\N	postponed	2026-02-06 08:24:50	25	1
167	Файл резервного копирования поврежден. Нарушена логика работы оборудования.	\N	in_progress	2026-02-06 10:23:21	47	4
168	Проблему решили, клиент поменял местами редуктора, а настройки оставил прежние(ускорения). Станок работает. 	resolved	completed	2026-02-06 10:43:15	48	3
250	нет излучения ю.	\N	in_progress	2026-02-16 08:53:09	69	4
325	+79095586622 Михаил	\N	in_progress	2026-03-23 09:24:12	92	3
329	задачу в работе в црм	\N	in_progress	2026-03-24 09:00:31	94	4
169	Подключили завод , самостоятельно не получается решить проблему . При накатке обоих бекапов оджна и таже ошибка . сами датчики , работают , ПЛК реагирует на них . при замыкании срабатывают.	\N	in_progress	2026-02-06 10:53:13	47	4
170	отложену жду овтет завода	\N	postponed	2026-02-06 11:28:45	47	4
171	в работе	\N	in_progress	2026-02-06 11:29:06	51	4
172	Рез стоит на азоте а пробивка на кислороде . сменили , ожидаю обратную связь.	\N	in_progress	2026-02-06 11:35:34	51	4
173	Проблема решена , неправильно настроили программу резки 	resolved	completed	2026-02-06 12:04:59	51	4
174	при обнулении станка , уходит в ошибку . 	\N	in_progress	2026-02-06 12:05:56	33	4
175	Запросил информацию у завода. 	\N	in_progress	2026-02-06 12:30:24	33	4
176	жду ответа от Китая , отложил 	\N	postponed	2026-02-06 12:30:42	33	4
177	В работу принял , 	\N	in_progress	2026-02-06 12:31:16	52	4
178	Почистили Керамику , и откалибровали . Всё работает. Хотели купить новую керамику посоветовал у нас , говорят дорого . 	resolved	completed	2026-02-06 12:31:55	52	4
179	Перевёл работу источника на цифру. Станок режет, работает.	resolved	completed	2026-02-06 12:54:26	49	3
180	Клиенту рекомендовал поменять этот параметр. Отвечает очень долго, в случае повторного обращения, открою задачу снова.	resolved	completed	2026-02-06 13:14:34	34	3
181	Новости не очень хорошие, китайцы максимально игнорят мой вопрос, решение и что требуется для замены, Саня ВЭД им писал, ему тоже не отвечают.	\N	postponed	2026-02-06 13:16:23	25	3
182	Уже сделано. Писали напрямую в телеграмм.	\N	in_progress	2026-02-06 13:27:35	53	3
183	На источнике закончилась лицензия. Код ввел, станок работает.	resolved	completed	2026-02-06 13:29:08	53	3
184	Клиент сказал перезвонит	\N	in_progress	2026-02-06 14:43:23	50	3
185	По телефону с ним созвонимся, решим проблему.	\N	postponed	2026-02-06 14:57:57	50	3
186	В ходе технической поддержки и удаленной поддержки сервисников из Китая , устранили неисправность . Концевик оси Y2 сьехал с основного положения ,  труборез выдавал ошибку . Проблему устранили , станок обнулили , всё работает в штатном режиме.	resolved	completed	2026-02-09 05:06:00	47	4
187	у клиента уже есть открытая рекламация, на связь клиент не выходит , информацию не тпредоставил .	transferred_to_claims	completed	2026-02-09 05:07:44	11	4
188	При запуске оборудования , захват бьёт о станину "не останавливается" вероятнее всего проблема в концевом датчике (он не срабатывает) , дистанционно эта проблема не решается . "оператор утверждает что датчики работают " требуется выезд специалиста , так как там еще ряд проблем с оборудованием (этим и сверлильно присадочным) 	transferred_to_claims	completed	2026-02-09 05:45:10	33	4
189	Нет конкретной проблемы , каждый раз разная , оборудование налаженно но каждый день (со слов клиента) появляется новая ошибка. Просят приехать инженера для наладки. Дистанционно в рамках тех.поддержки не установить . природу неисправности.	\N	in_progress	2026-02-09 05:55:02	32	4
190	.	transferred_to_claims	completed	2026-02-09 05:55:10	32	4
191	Клиенту уменьшили рабочий стол, так как кожух пылеуловителя задевал стойку портала. Так же по собранной информации дал ответ по поводу калибровки инструмента. По заводу так макрос прописан.	resolved	completed	2026-02-09 10:33:57	50	3
192	Запросил информацию у клиента , сказал скинет в макс , просит провести ему обучение по работе с данным станком , а так же просит установить в чём проблема ( когда устанавливают определенный угол , он сбивается постоянно)	\N	in_progress	2026-02-09 12:36:16	54	4
193	Жду информацию.	\N	postponed	2026-02-09 12:36:25	54	4
194	Завтра буду звонить клиенту.	\N	in_progress	2026-02-09 15:04:20	56	3
195	.	\N	postponed	2026-02-09 15:04:30	56	3
196	Сергей, нужно эскалировать вопрос наверх. 	\N	postponed	2026-02-09 15:46:19	25	1
197	Течет масло с гидробака 	\N	in_progress	2026-02-10 05:02:57	55	4
198	висит глаз режет 	resolved	completed	2026-02-10 06:02:20	4	4
199	Принял	\N	in_progress	2026-02-10 06:13:50	58	1
200	Решил.	resolved	completed	2026-02-10 06:14:51	58	1
201	Запросил фото узла и места крепления. С клиентом поговорил, подшипник целый.	\N	in_progress	2026-02-10 06:31:22	56	3
202	Наверх, это кому?Саня сегодня еще будет писать китайцам	\N	postponed	2026-02-10 06:39:10	25	3
203	Ожидаю дополнительную ,диагностическую информацию. 	\N	in_progress	2026-02-10 07:13:24	55	4
204	.	\N	postponed	2026-02-10 07:13:27	55	4
205	Клиент должен скинуть фото.	\N	postponed	2026-02-10 07:46:22	56	3
206	Буду звонить клиенту	\N	in_progress	2026-02-10 08:12:57	57	3
207	Жду фото от клиента.	\N	postponed	2026-02-10 08:17:05	57	3
251	Замерили напряжения между pwm+ и pwm- , 21 вольт при необходимых 24 . заменили реле всё работает 	\N	in_progress	2026-02-16 08:53:43	69	4
252	.	resolved	completed	2026-02-16 08:53:49	69	4
327	Завтра с клиентом созвонимся	\N	in_progress	2026-03-23 13:10:44	93	3
208	Спустя 10 минут после запуска оборудования , произошла утечка масла из бака расширителя , неисправность такого рода могла произойти в следствии ( неисправного насоса) со слов клиента насос шумит иначе чем обычно , засор магистрали масла или фильтра , перелив масла . Так же есть жалобы на периодическое отключение ,  дистанционно не удаётся диагностировать полностью и устранить неисправность , передал в рекламации.	transferred_to_claims	completed	2026-02-10 11:17:38	55	4
209	Клиент сообщил, что после переустановки узла, станок начал пилить ровно. Подшипники оказались целыми. Будут наблюдать дальше и сообщат если опять проблема вернётся.	resolved	completed	2026-02-10 12:23:00	56	3
210	Клиенту будет выслан новый насос.	resolved	completed	2026-02-10 12:40:43	25	3
211	Клиенту дал рекомендации, жду когда сделает.	\N	postponed	2026-02-10 12:41:19	57	3
212	Буду связыватся с клиентом, ответа нет от него\n	\N	postponed	2026-02-11 06:19:57	57	3
213	Буду звонить уточнять	\N	in_progress	2026-02-11 08:41:09	60	3
214	С клиентом связался , запросил информацию 	\N	in_progress	2026-02-11 09:19:46	59	4
215	У китайцев запросил чертёж/схема, ожидаю, станок не на гарантии	\N	postponed	2026-02-11 09:45:39	60	3
216	ожидаю информацию 	\N	postponed	2026-02-11 10:55:21	59	4
217	Клиент не отвечает\n	\N	postponed	2026-02-11 12:27:33	57	3
218	жду ответа от китайцев, так же дал рекомендации по разбору патрона, клиент хочет быстрее приступить к работе.	\N	postponed	2026-02-11 12:55:37	60	3
219	Клиент перестал выходить на связь .При повторном обращении возобновлю задачу.	resolved	completed	2026-02-12 07:08:45	59	4
220	Клиент перестал выходить на связь . при повторном обращении возобновлю задачу.	resolved	completed	2026-02-12 07:09:20	54	4
221	Идёт дым при запуске . запросил фото узла неисправности , ожидаю .	\N	in_progress	2026-02-12 07:29:12	61	4
222	От китайцев схем не дождаться, клиент самостоятельно разобрал этот узел. 	resolved	completed	2026-02-12 07:30:50	60	3
223	1	\N	postponed	2026-02-12 09:07:39	61	4
224	Принял	\N	in_progress	2026-02-12 09:08:06	63	4
225	Клиент не отвечает	\N	postponed	2026-02-12 09:09:54	57	3
226	Буду звонить запрашивать информацию.	\N	in_progress	2026-02-12 09:10:19	62	3
227	Ожидаю фото и видео.	\N	postponed	2026-02-12 09:18:35	62	3
228	Буду звонить клиенту.	\N	in_progress	2026-02-12 09:19:10	65	3
229	Буду звонить, расскажу	\N	in_progress	2026-02-12 09:26:00	65	3
230	Клиенту сообщил о наличии мест под крюки в нижней части станка(спереди, сзади).	resolved	completed	2026-02-12 09:30:51	65	3
231	1	\N	postponed	2026-02-12 09:31:28	63	4
232	1	\N	in_progress	2026-02-12 09:31:42	64	4
233	1	\N	postponed	2026-02-12 09:31:51	64	4
234	Подключился удалённо	\N	in_progress	2026-02-12 09:32:23	64	4
235	при запуске CypCutE  зависает весь компьютер . Завод рекомендовал переустановить программное обеспечение , клиент сказал что не компьютерщик . Ожидаю удаленное подключение.	\N	in_progress	2026-02-12 10:02:18	64	4
236	Отложили до завтра .	\N	postponed	2026-02-12 11:16:42	64	4
237	Рекомендация предоставлена . стаб надо выбирать на 20-30% от номинала . для этой модели от 22 до 30 квт . рекомендовал 30кВт и забыть о проблемах.	resolved	completed	2026-02-12 11:46:57	63	4
238	Всё есть на видео.	transferred_to_claims	completed	2026-02-12 13:19:08	62	3
239	Клиенту было предложено положить правило либо что то ровное и длинное на стол на который кладут материал, что бы увидеть есть ли на нём деформация, так как с левой стороны нож на 2 мм врезается в сам стол, в середине не доходит, справа тоже на 1 мм врезается. Делаю вывод, что стол деформировался. Жду фото с зазорами от клиента. Давление в гидравлике в норме, отрабатывает хорошо.	\N	postponed	2026-02-13 07:38:08	57	3
240	Судя по всему сгорела катушка соленойда . Запросил замерить входящее на катушку  напряжение.	\N	in_progress	2026-02-13 08:54:22	61	4
241	Был конфликт программного обеспечения с виндовс , при запуске сипконфиг перезагружало компьютер.  переустановили по новой сипкат . настроили откалибровали . сё работает в штатном режиме. 	resolved	completed	2026-02-13 11:09:47	64	4
242	буду звонить.	\N	in_progress	2026-02-13 13:31:26	66	3
243	Клиент недоступен	\N	postponed	2026-02-13 13:31:36	66	3
244	В понедельник скинет фото резинок, воздуховода, замерит их диаметры.	\N	postponed	2026-02-13 14:06:22	66	3
245	Клиент не прислал до сих пор информацию, вероятнее всего деформирован стол, куда кладут материал.	resolved	completed	2026-02-16 06:38:13	57	3
246	ожидаю информацию	\N	postponed	2026-02-16 06:43:44	61	4
247	Не работает гидравлический узел , ситуация повторилась , только теперь на большом двигателе . Там стоит НША 50. 	\N	in_progress	2026-02-16 06:48:34	68	4
248	Дистанционно такое не решается , передаю информацию. в рекламационный отдел .	transferred_to_claims	completed	2026-02-16 06:50:35	68	4
328	...	\N	postponed	2026-03-23 13:10:54	93	3
253	1)вентиляционная труба диаметр 100мм длина 630мм*- 2шт\n2)уплотнительная резина по внутреннему размеру 100мм,толщина 9мм- 8шт\nРезина вся в трещинах, воздуховоды порваны, клиент просит прислать новые.	transferred_to_claims	completed	2026-02-16 10:03:22	66	3
254	Буду звонить, уточнять информацию.\n	\N	in_progress	2026-02-16 10:04:59	70	3
255	Связался  , запросил удалённое подключение	\N	in_progress	2026-02-16 10:20:14	70	4
256	.	\N	postponed	2026-02-16 10:20:37	70	4
257	Оборудование не на гарантии . Дал рекомендации . греется сверху значит оптические элементы . замена защитки не помогает ,  скорее всего фокусная или каллиматорная . предложил самостоятельно разобрать осмотреть . Не стали рисковать . Предложил выезд специалиста . 	\N	in_progress	2026-02-16 10:26:08	67	4
258	.	\N	postponed	2026-02-16 10:27:33	67	4
259	Починил. Надо было в сибконфиге поновой подгрузить соединения . плата БЦЛ потеряла связь с станком .	resolved	completed	2026-02-16 10:52:45	70	4
260	направили КП для выезда специалиста	transferred_to_service	completed	2026-02-16 14:43:14	67	4
261	в работе 	\N	in_progress	2026-02-17 10:07:57	71	4
262	Цель задачи настроить хеминг . клиент хочет выполнить процедуру плющевания . 	\N	in_progress	2026-02-17 10:12:07	71	4
263	- нарисуйте профиль открытой матрицы с углубленной выровненной частью и установите 0.001 мм для линий выравнивания (как будто матрица закрыта);\n- переведите секцию к линии, определяемой как фальцевание (вертикальная длина);\n- нажмите [Flang.];\n- линия, определяемая как выровненная часть, будет на рисунке заштрихованной;\n- после того, как матрица будет нарисована, нажмите [Die dimensions] и установите значение 1 в поле ”Pneumatic (1=yes 0=no)”;\n- Нажмите [Ok];\n- с этого момента функция 2 будет автоматически включаться при фальцевании.	\N	in_progress	2026-02-17 10:28:46	71	4
264	Соберу информацию и буду писать китайцам.	\N	in_progress	2026-02-17 11:22:54	72	3
265	Позвонил, клиент устранил неисправность, всё работает	resolved	completed	2026-02-17 11:27:22	72	3
266	Если мы дадим пароль, они не ушатают станок?	\N	in_progress	2026-02-17 13:19:59	71	1
267	Предоставил рекомендации , можно в двух вариантах выполнять программу , путём установки угла гиба и путём установки координат . Так же сформировал инструкцию по выполнению программы относительно руководства по эксплуатации . Что касаемо пароля : он в открытом доступе в предоставленном им руководстве по эксплуатации . Ушатать  не должны , Рафаэль опытный инженер , я его информировал о том что можно расклинить инструмент если задать неверные координаты .	\N	in_progress	2026-02-17 14:14:28	71	4
268	.	\N	postponed	2026-02-18 05:34:22	71	4
269	Позвоню клиенту.	\N	in_progress	2026-02-18 07:29:30	73	3
270		\N	in_progress	2026-02-18 07:57:03	74	4
271	Всё настроили 	resolved	completed	2026-02-18 14:04:25	71	4
272	На соленойде не хватает пластмассовой гайки , подсказал куда подаётся воздух записал короткое видео о том как работает станок . Предаю в рекламации по поводу недоукомплектованности станка .	transferred_to_claims	completed	2026-02-19 05:26:09	74	4
273	Принял другую заявку	\N	postponed	2026-02-19 07:21:44	73	3
274	Клиент говорит, что механика вся в норме, с клиентом меняли параметры ускорения - не помогло. Нужен выезд инженера, для комплексной проверке и устранения данной неисправности.	\N	in_progress	2026-02-19 07:21:44	75	3
275	Передано в рекламацию.	transferred_to_claims	completed	2026-02-19 07:22:24	75	3
276	Жду фото и видео материал, пока ничего нет\n	\N	postponed	2026-02-19 11:36:05	73	3
277	Позвоню, уточню	\N	in_progress	2026-02-19 11:36:52	76	3
278	3 заготовки пилит хорошо, на 4 фрезы врезаются в лапу и обламываются. Никаких ошибок на станке не выходят. Отправил китайцам жду ответ.	\N	postponed	2026-02-19 13:31:17	76	3
279	работа уже ведется	\N	in_progress	2026-02-19 13:47:08	77	3
280	У клиента прыгает голова при резке на углах. На керамике резинки нет, лист металла между листорезом и труборезом лежит, чертёж говорят совпадает с фактическим размером труб. высоту реза меняли от 0,8-1,5, результат тот же. Калибровка по ёмкости хорошая 6900, гипербола идеальная. Дальше разбираемся.	\N	postponed	2026-02-19 13:50:38	77	3
281	Позвоню, уточню	\N	in_progress	2026-02-20 06:34:15	78	3
282	Написал китайцам, у них праздник там, буду ждать ответ.	\N	postponed	2026-02-20 07:46:06	76	3
283	Клиент ничего не прислал, как скинет создам новую заявку.	resolved	completed	2026-02-20 08:00:07	73	3
284	.	\N	in_progress	2026-02-20 08:00:16	79	4
285	Механическое повреждение шарико-винтовой пары "швп" оси Z , так же повреждён сальник гайки ШВП . Весь блок под замену. 	\N	in_progress	2026-02-20 08:15:55	79	4
286	Передаю в рекламации. 	transferred_to_claims	completed	2026-02-20 08:16:13	79	4
326	Клиент сообщил, что проблема решена.	resolved	completed	2026-03-23 09:54:41	92	3
287	В машин конфиге кто то изменил номинальное значение мощности лазера, что никак не отражается на его работе, изменил значение на положенные 12000, кто то поставил 4000. 	resolved	completed	2026-02-20 08:26:07	78	3
288	Внесли программные корректировки, клиент будет работать, сообщит если ещё раз проявится	resolved	completed	2026-02-20 14:23:48	76	3
289	Заварили металлические пластины по всем ногам, клиент говорит стало намного лучше, но всё равно до конца проблема не ушла.	\N	postponed	2026-02-20 15:01:55	77	3
290	Ожидаю видеоматериалы	\N	in_progress	2026-02-24 06:17:19	80	4
291	Клиент работает, голова не прыгает. Будут наблюдать, в случае чего сообщат.	resolved	completed	2026-02-24 12:04:18	77	3
292	Подключился китаец поставил натсройку , всё нормально 	resolved	completed	2026-02-24 12:23:04	80	4
293	зашёл в эксперт меню , удалили натсрйоки , сменил на операторское меню.	\N	in_progress	2026-02-24 12:28:13	83	4
294	готово	resolved	completed	2026-02-24 12:28:20	83	4
295	Подключаюсь\n	\N	in_progress	2026-02-24 12:28:38	82	4
296	подключился удалили 	resolved	completed	2026-02-24 12:31:06	82	4
297	Позвоню, уточню	\N	in_progress	2026-02-24 13:24:04	81	3
298	Жду фото, клиент хочет самостоятельно заменить трансформатор, т к на заводе сильные перепады напряжения, пришлёт фото того какой хочет поставить.	\N	postponed	2026-02-24 13:30:39	81	3
299	С клиентом связался , запросил информацию , Виталий не культурно общается , предвзято . связался с Германом Юрьевичем запросил информацию ожидаю. Проблема заключается в том что в процессе работы выбило автомат на их линии , после этого перестало оборудование запускаться с кнопки , в принудительном "ручном " режиме  оборудование работает . 	\N	in_progress	2026-03-04 13:03:46	86	4
300	.	\N	postponed	2026-03-04 13:03:53	86	4
301	Заменено, работает.	resolved	completed	2026-03-04 13:49:02	81	3
302	По видео видно что искрит пускатель в момент принудительного запуска . кнопка пуск не активна . видимой неисправности нет . Автоматика отрабатывает . значит возможно есть проблемы в входящей цепи . так же есть несколько вариантов решения проблемы . клиенту производитель может собрать шкаф упрощенный без автоматики "без функции автореверса" и оборудование будет работать . или могут отправить свой шкаф производителю и там все будет работать хорошо (со слов производителя) . Передаю в рекламации . 	transferred_to_claims	completed	2026-03-05 06:17:56	86	4
303	Позвоню, соберу информацию	\N	in_progress	2026-03-05 10:56:24	87	3
304	Ошибку по Z убрал, всё работает.	resolved	completed	2026-03-05 12:31:04	87	3
305	работал с утра с ним	\N	in_progress	2026-03-05 13:07:54	88	3
306	851414  код был предоставлен клиенту. Про замену упоров ничего не было сказано	resolved	completed	2026-03-05 13:09:34	88	3
307	Возврат в работу. Закрыта по ошибке.	\N	in_progress	2026-03-05 14:26:22	88	1
308	Звонил Роману Егорову, трубку не взял, завтра повторно буду звонить	\N	postponed	2026-03-05 14:27:21	88	3
309	Роман Егоров 89255390424 абонент не берёт трубку	\N	postponed	2026-03-06 06:28:44	88	3
310	код 71817, про оснастку сказал с менеджером будет общаться.	resolved	completed	2026-03-06 08:52:01	88	3
311	в работе 	\N	in_progress	2026-03-06 09:05:26	89	4
312	связался с клиентом . клиент негативит . говорит что с нашей компанией связанны негативные эмоции по поводу прошлого обращения . относительно матрицы , в данно ситуации проблема заключается в том что не работает компенсация недогиба . необходимо будет связаться с производителем оборудования относительно этой проблемы . запросил материалы тех.процесса ожидаю 	\N	in_progress	2026-03-06 09:27:25	89	4
313	.	\N	postponed	2026-03-06 09:27:32	89	4
314	клиент отложил на после выходных 	\N	postponed	2026-03-06 13:54:17	89	4
315	клиент перестал выходить на связь . при повторном обращении возобновклю задачу.	resolved	completed	2026-03-11 08:34:22	89	4
316	Сергей, прошу повторно связаться с Константином и выяснить текущую ситуацию.	\N	in_progress	2026-03-12 07:23:55	87	1
317	Хорошо	\N	in_progress	2026-03-12 07:24:45	87	3
318	В оптике лазерной головы имеются точки.	resolved	completed	2026-03-13 09:09:56	87	3
319	531456156	\N	in_progress	2026-03-13 12:24:07	91	3
320	Клиент не взял трубку	\N	in_progress	2026-03-13 12:24:33	91	3
321	ожидаю	\N	postponed	2026-03-13 12:24:41	91	3
322	+79774859257 Михаил, трубку не берёт	\N	postponed	2026-03-17 11:56:16	91	3
323	1) Клиенту необходимо провести полное ТО станка. Со снятием плиты и смазкой ШВП.\n2) Необходимо заменить защитный колпачок коннектора.\n3) Необходимо заменить водяной насос чиллера. 220V\n4) Диагностика оптических элементов лазерной головы.\nТак же клиент готов к доп. оплате если в ходе ТО выявятся скрытые неисправности чего либо.	transferred_to_claims	completed	2026-03-17 12:35:26	91	3
324	Буду звонить, запрошу фото/видео.	\N	in_progress	2026-03-23 09:23:32	92	3
330	в работе в црм	resolved	completed	2026-03-24 09:00:44	94	4
331	Клиенту сообщил, что для резки на 2 станках хватит установки доп ресивера на 500 литров.	resolved	completed	2026-03-24 11:20:02	93	3
332	работаем над этим	\N	in_progress	2026-03-25 12:26:23	95	3
333	китаец прислал чертёж их детали, скинул клиенту.	resolved	completed	2026-03-26 07:15:09	95	3
334	позвоню, уточню \n	\N	in_progress	2026-03-26 07:22:42	96	3
335	jnkj;tyj	\N	postponed	2026-03-26 09:05:38	96	3
336	Ожидаю когда произведут манипуляции	\N	in_progress	2026-03-31 05:26:03	99	4
337	Необходимо прочистить датчики концевые , и проверить работоспособность.	\N	in_progress	2026-03-31 05:27:08	99	4
338	жду	\N	postponed	2026-03-31 05:27:34	99	4
339	Направил рекомендации проверить проток воды . направил руководство по эксплуатации чиллера , запросил модель контроллера что бы прислать руководство .	\N	in_progress	2026-03-31 05:28:29	97	4
340	фото видео прислать не могут интернета нет\n	\N	in_progress	2026-03-31 05:28:39	97	4
341	жду обратную связь	\N	postponed	2026-03-31 05:28:47	97	4
342	клиенту скинули видео с настройкой . в данном видео аут 11 это назначается режим работы тахометра , далее аут 1 L (low) нижний порог диапазона оборотов ,аут 1 H(hight) верхний порог диапазона оборотов . ну обычно с завода ставится настройка сразу . для вашего оборудования можно установить от 525 до 1025 . при достижении маховика 525 оборотов , контроллер подаст питание на соленойд и он направит поток гидравлической жидкости в рабочую магистраль и запустит валы подачи . если обороты выйдут за предел этого диапозона то валы подачи остановятся .	\N	in_progress	2026-03-31 08:25:38	98	4
343	настрйока с завода стоит . если что натсроит по видео как ему удобно .	resolved	completed	2026-03-31 08:26:02	98	4
344	проблему устранили . 	resolved	completed	2026-04-01 07:05:49	97	4
345	в работе	\N	in_progress	2026-04-01 07:13:44	100	4
346	Требуется ПНР 	transferred_to_service	completed	2026-04-01 07:13:59	100	4
347	датчики отчистили от окалин , все работает .	resolved	completed	2026-04-01 07:22:57	99	4
348	Рекомендации клиенту предоставил . 1. Натяжение шлифовальной ленты\nНа станках этого типа используется пружинный механизм фиксации на краях барабана:\nПроцесс: Лента наматывается на барабан по спирали (встык, без нахлеста). Концы ленты заправляются в специальные зажимы по краям барабана.\nАвтоматическое натяжение: Внутри одного из зажимов установлена мощная пружина. После того как вы заправили край ленты, пружина тянет зажим в сторону, постоянно поддерживая натяжение ленты в процессе работы. Это компенсирует небольшое растяжение материала при нагреве.\nВажно: Следите, чтобы витки ленты не перекрывали друг друга, иначе на заготовке появятся глубокие полосы или лента порвется.\n2. Регулировка барабана по высоте\nВ двухбарабанных станках высота настраивается в двух плоскостях: общая высота обработки и разница между барабанами.\nОбщая высота (подъем стола): Регулируется основным маховиком на корпусе станка. Он поднимает или опускает весь рабочий стол относительно барабанов. Один полный оборот маховика обычно соответствует 1.5–2 мм подъема.\nРегулировка второго барабана: Чтобы станок работал корректно, второй барабан (чистовой) должен быть настроен чуть ниже первого (обычно на 0,1–0,15 мм).\nПо бокам опор второго барабана находятся регулировочные винты или эксцентрики.\nОслабив стопорные болты, вы можете микровинтами опустить второй барабан так, чтобы он едва касался заготовки после прохода первого.\nВыравнивание параллельности: Если станок шлифует «клином» (одна сторона заготовки тоньше другой), необходимо отрегулировать опоры барабана с одной стороны, добиваясь идеальной параллельности поверхности стола.	\N	in_progress	2026-04-01 10:46:13	96	4
349	Клиент не даёт обратной связи	\N	in_progress	2026-04-02 12:39:26	96	4
350	Принял другую заявку	\N	postponed	2026-04-03 11:02:34	96	4
351	В работе 	\N	in_progress	2026-04-03 11:02:35	101	4
352	При зажатии бабки , сифонит воздух. Отложили на понедельник , в понедельник разберет бабку и отфотографирует все элементы . 	\N	postponed	2026-04-03 11:34:36	101	4
353	Позвоню, запрошу инфу	\N	in_progress	2026-04-06 08:08:50	102	3
354	Звонил оператору Максиму +79120284280, не отвечает	\N	postponed	2026-04-06 08:23:04	102	3
355	Запросил фото видео	\N	postponed	2026-04-06 08:55:36	102	3
356	Клиент вызовет электрика, для проведения необходимых замеров.	\N	postponed	2026-04-06 09:25:59	102	3
357	запросил информацию	\N	in_progress	2026-04-06 14:30:16	103	3
358	нужно DXF перевести в DLD формат 	\N	postponed	2026-04-06 14:30:41	103	3
359	в работе\n	\N	in_progress	2026-04-06 14:43:01	104	4
360	отложили на завтра	\N	postponed	2026-04-06 14:43:08	104	4
361	Клиенту отправил инструмент в формате DLD.	resolved	completed	2026-04-07 07:03:24	103	3
362	Электрик пришёл, мерит всё	\N	postponed	2026-04-07 07:11:47	102	3
363	У клиента вышел из строя серводрайвер. Нужна замена. Нужен выезд инженера для замены 	transferred_to_claims	completed	2026-04-07 08:37:18	102	3
364	позвоню, запрошу информацию	\N	in_progress	2026-04-08 07:40:33	105	3
365	Ожидаю информацию	\N	postponed	2026-04-08 07:46:08	105	3
366	С клиентом созвонился, завтра договорились на удалённое подключение.	\N	in_progress	2026-04-08 11:31:08	106	3
367	.	\N	postponed	2026-04-08 11:31:12	106	3
368	Платные услуги	\N	in_progress	2026-04-09 06:16:33	107	1
369	Передано Татьяне в работу.	transferred_to_service	completed	2026-04-09 06:16:46	107	1
370	Тест	\N	in_progress	2026-04-09 06:16:57	90	1
371	Тест	resolved	completed	2026-04-09 06:17:05	90	1
372	тест	\N	in_progress	2026-04-09 06:17:17	85	1
373	тест	resolved	completed	2026-04-09 06:17:21	85	1
374	звонил 9,04 .не отвечает . должен был скинуть видео фото того что происходит . его оператор сказал что не может скинуть в телеграмме , договорились что зальёт на яндекс диск и скинет ссылку . Закрываю до повторного обращения. 	resolved	completed	2026-04-09 06:36:07	104	4
375	Связался с клиентом , ожидаю обратную связь , о актуальности . 	\N	postponed	2026-04-09 06:39:37	101	4
376	Рекомендации предоставлены клиенту , клиент перестал выходить на связь .	resolved	completed	2026-04-09 06:40:47	96	4
377	Завтра их оператор позвонит когда будет возле станка . и будет выполнять рекомендации. 	\N	in_progress	2026-04-09 12:54:09	108	4
378	.	\N	postponed	2026-04-09 12:54:16	108	4
379	Обратной связи не последовало , возобновлю после повторного обращения. 	resolved	completed	2026-04-10 06:04:45	101	4
380	В процессе эксплуатации лазерного станка произошел нештатный выброс технологической жидкости из сопла режущей головы. Предположительно, причиной послужил резкий перепад давления в пневмосистеме (обратный удар от компрессора). В результате выброса произошло загрязнение внутренней полости защитного стекла лазерной головы.\nРезультаты первичной диагностики:\n1.\tПосле замены защитного стекла обнаружено наличие мелкодисперсной пыли на его верхней стороне, что свидетельствует о проникновении загрязнений в оптический тракт выше посадочного места стекла.\n2.\tЗафиксирована потеря калиброванной емкости (расстройка емкостного датчика управления высотой сопла ). В процессе резки наблюдается нестабильное детектирование положения листа (потеря сигнала обратной связи по емкости).\n3.\tПредварительная проверка показала:\no\tЭлектрические контакты разъемов визуально целы (окисление/повреждения не зафиксированы);\no\tКерамический разъем (изолятор) не имеет видимых трещин и сколов;\no\tКоаксиальный кабель протянут с соблюдением допустимого радиуса изгиба, механических повреждений не обнаружено.\nОсновная предполагаемая причина неисправности - попадание технологической жидкости (конденсата/эмульсии) во внутреннюю полость режущей головы, в зону расположения контактов емкостного датчика и измерительной платы. Жидкость или ее пары создают токи утечки, изменяя диэлектрическую проницаемость среды между сигнальным и экранным контурами. Это приводит к шунтированию высокочастотного измерительного сигнала датчика расстояния до листа (емкостного CFC-сенсора), следствием чего является некорректное определение положения сопла относительно материала (потеря емкости) и, как результат, сбои в управлении оси Z в процессе резки.\nРекомендуемое техническое решение\nДля восстановления работоспособности необходима полная ревизия и сервисное обслуживание лазерной режущей головы:\n1.\tДемонтаж головы с портала станка.\n2.\tРазборка оптико-механического узла в чистом помещении (или с применением ламинарного стола) для исключения вторичного загрязнения оптики.\n3.\tДефектовка уплотнений - проверка состояния кольцевых уплотнителей (сильфонов, OR-рингов) между секциями головы, через которые могла произойти инфильтрация жидкости.\n4.\tСушка внутренних полостей и контактных групп (колодка CFC-сенсора, пружинные контакты керамического держателя, контакты датчика столкновения) \n5.\tОчистка контактов спирто-бензиновой смесью или специальным очистителем удаление следов оксидной пленки.\n6.\tПроверка сопротивления изоляции между центральным контактом и корпусом головы - значение должно соответствовать спецификации производителя (обычно не менее 1 МОм для исправного тракта емкостного датчика).\n7.\tКалибровка датчика высоты после сборки согласно регламенту производителя головы.\nВывод\nБез проведения указанной ревизии и сушки контактной группы емкостного датчика восстановление корректного измерения емкости (работоспособности системы слежения за листом) маловероятно из-за сохраняющегося влияния остаточной влажности на измерительный тракт.	\N	in_progress	2026-04-10 12:07:51	108	4
381	Необходим выезд на место дистанционно затрудняюсь 	transferred_to_claims	completed	2026-04-10 12:25:04	108	4
382	Клиент сегодня в течении дня попробует поменять кнопки местами, напишет сказал	\N	postponed	2026-04-13 06:43:33	105	3
383	Подключался к клиенту удалённо, расстояния поднятия механизмов поддержки труб прописаны корректно, рекомендовал клиенту проверить провода подключения клапана и пневматическую линию. Проблема в том что один из механизмов поддержки труб срабатывает некорректно.	resolved	completed	2026-04-13 06:50:16	106	3
384	С клиентом на связи . В ходе диагностики было выявленно: Дефект силовой установки подрезной пилы\nСильная вибрация и акустический шум: При включении электродвигателя подрезной пилы наблюдаются интенсивная низкочастотная вибрация и выраженный гул, не характерные для нормальной работы узла.\nРезультаты первичной диагностики:\nКлиновой ремень натянут штатно (автоматическая система натяжения исправна).\nКрепление электродвигателя визуально не имеет ослаблений и перекосов (соосность валов и параллельность шкивов подлежат инструментальной проверке).\nВероятная причина: износ, коррозия или разрушение сепаратора, закрытых подшипников шпинделя подрезной пилы. Подшипники относятся к необслуживаемому типу ,что подтверждается стр. 20 руководства по эксплуатации. Наличие ржавчины на основном столе при приемке указывает на хранение станка во влажной среде, что могло привести к коррозии внутренних элементов подшипников.\n	\N	in_progress	2026-04-13 07:35:40	109	4
385	Запросил дополнительную диагностику на : механические ослабление крыльчатки двигателя, соосность шкива двигателя подрезной пилы с валом самой подрезной пилы . Вероятнее всего подшипник вышел из строя , меняется в сборе с узлом подрезной пилы . 	\N	in_progress	2026-04-13 07:37:04	109	4
386	Ожидаю информацию	\N	postponed	2026-04-13 11:21:59	109	4
387	От китайцев ответа нет, LTT центр кроме бредовых вариаций нажатия клавиш, больше ничего сказать не могут. Скорее всего не работает клавиша Enter на панели управления. 	transferred_to_claims	completed	2026-04-13 11:53:39	105	3
388	.	\N	in_progress	2026-04-14 07:46:25	110	3
389	Требуется замена цилиндра.	transferred_to_claims	completed	2026-04-14 07:47:10	110	3
390	диагностику продолжаем	\N	in_progress	2026-04-14 12:53:05	109	4
391	.	\N	postponed	2026-04-14 12:53:12	109	4
392	Неисправность дистанционно не устранить , необходима замена узла подрезной пилы . А так же гуляет каретка продольного перемещения , есть люфт .	transferred_to_claims	completed	2026-04-14 13:34:35	109	4
393	Предоставил рекомендации прочистить клапаны	\N	in_progress	2026-04-14 14:18:28	111	4
394	Прочистили гидравлические узлы , меняли электрические компоненты не помогает . чистим основной гидроузел 	\N	in_progress	2026-04-16 07:40:10	111	4
395	1	\N	postponed	2026-04-16 07:40:13	111	4
396	Отключите питание переподключите кабели энкодера и питания двигателя оси Z\n10:27\nпроверьте по манистрали нет ли гшджето перетертостей кабеля\n10:27\nсигнал теряет ,  ошибка Еc это потеря связи с энкодером . может быть из за помех на линии перотертостей кабеля , плохой контакт .\n10:32\nв краййнем случае неисправность энкодера или самого серводрайвера . но это редкость\n10:33\nПрослеживаются ли какие то закономерности в неисправности ? Например положении головы относительно портала или положение порттала относительно станины	\N	in_progress	2026-04-16 07:40:21	112	4
397	рекомендации предоставил ожидаю обратную связь 	\N	in_progress	2026-04-16 07:40:40	112	4
398	Попросил зафиксировать сигналы на стойке 	\N	postponed	2026-04-17 07:55:13	111	4
399	Прочистили контакты , перестали выходить на связь .При повторном обращении возобновлю задачу . Проблема кроентся либо в помехах либо в энкодере . 	resolved	completed	2026-04-17 07:56:35	112	4
400	Запросил информацию ,ожидаю 	\N	in_progress	2026-04-21 06:02:22	113	4
401	 Станок заблокирован программно, ранее был у нас на ремонте (со слов клиента после ремонта ушел в демо режим и не переключает режимы работы ). Контроллер требует ввода кода активации (лицензии). Без этого переключение режимов («резка», «чистка») и работа будут недоступны. Обычно это делается производителем после покупки или при наступлении срока демо-режима (на фото дата 2017-01-01 - возможно, сбросились внутренние часы или истек тестовый период). Порекомендовал изменить дату на актуальную , если будет просить пароль рекомендовал ввести стандартный 123456 или 000000 . Если рекомендация не поможет , буду обращаться на к к производителю . 	\N	in_progress	2026-04-21 06:53:23	113	4
402	Производитель  предоставили пароль 666888 , жду обратную связь 	\N	in_progress	2026-04-21 09:18:18	113	4
403	.	\N	postponed	2026-04-21 09:18:21	113	4
404	позвонил , починили , звёздочка была установлена уже	\N	in_progress	2026-04-21 09:20:17	114	4
405	.	resolved	completed	2026-04-21 09:20:23	114	4
406	связываюсь , обратной связи нет . При повторном обращении возобновлю задачу. 	resolved	completed	2026-04-21 09:21:35	111	4
407	пароль предоставил . теперь он может редактировать параметры . 	resolved	completed	2026-04-21 13:05:19	113	4
408	.	\N	in_progress	2026-04-27 08:24:01	115	3
409	Ошибка на контроллере была вызвана скачком напряжения. Стабилизатор отсутствует, рекомендовал клиенту приобрести его. При перезагрузки станка ошибка ушла.	resolved	completed	2026-04-27 08:25:39	115	3
410	Пропал красный лучь , клиент просит выезд специалиста на место для устранения проблемы . 	\N	in_progress	2026-04-27 11:09:06	116	4
411	Выезд тут не нужен , но они боятся что у них ченить обновится .	transferred_to_claims	completed	2026-04-27 11:10:23	116	4
\.


--
-- Data for Name: support_ticket_media; Type: TABLE DATA; Schema: public; Owner: -
--

COPY "public"."support_ticket_media" ("id", "filename", "original_name", "mime_type", "size", "path", "created_at", "support_ticket_id", "thumbnail_path") FROM stdin;
1	media_696fc5d5061239.85447536.jpeg	photo_2025-08-21 21.18.20.jpeg	image/jpeg	121638	support-ticket/media/1/media_696fc5d5061239.85447536.jpeg	2026-01-20 18:13:41	1	support-ticket/media/1/thumb_media_696fc5d5061239.85447536.jpeg
2	media_6970747c5427a0.01970339.jpeg	photo_2025-08-21 21.18.21.jpeg	image/jpeg	151318	support-ticket/media/2/media_6970747c5427a0.01970339.jpeg	2026-01-21 06:38:52	2	support-ticket/media/2/thumb_media_6970747c5427a0.01970339.jpeg
3	media_69707484cbe702.85504979.mp4	workspace.mp4	video/mp4	279347	support-ticket/media/2/media_69707484cbe702.85504979.mp4	2026-01-21 06:39:01	2	media/2/thumb_media_69707484cbe702.85504979.mp4.jpg
4	media_6971f42d9957c8.16871527.png	SCR-20260119-myam.png	image/png	3129412	support-ticket/media/4/media_6971f42d9957c8.16871527.png	2026-01-22 09:55:58	4	support-ticket/media/4/thumb_media_6971f42d9957c8.16871527.png
5	media_6973500c92c549.15387948.mp4	workspace.mp4	video/mp4	279347	support-ticket/media/4/media_6973500c92c549.15387948.mp4	2026-01-23 10:40:12	4	media/4/thumb_media_6973500c92c549.15387948.mp4.jpg
6	media_6973564c3002b6.73557850.png	Снимок экрана 2026-01-23 в 14.06.31.png	image/png	61737	support-ticket/media/4/media_6973564c3002b6.73557850.png	2026-01-23 11:06:52	4	support-ticket/media/4/thumb_media_6973564c3002b6.73557850.png
7	media_6977116fa68f66.43105633.jpg	photo_5456239862462745594_y.jpg	image/jpeg	237465	support-ticket/media/7/media_6977116fa68f66.43105633.jpg	2026-01-26 07:02:07	7	support-ticket/media/7/thumb_media_6977116fa68f66.43105633.jpg
8	media_69772057df1eb7.91488581.png	e86f25ffd645a59b01263d21df62ec46.png	image/png	883280	support-ticket/media/8/media_69772057df1eb7.91488581.png	2026-01-26 08:05:44	8	support-ticket/media/8/thumb_media_69772057df1eb7.91488581.png
9	media_6977205ab9b045.41151873.mp4	9fdd76f2ee1a6565e815b306ccdf8137_raw.mp4	video/mp4	2053993	support-ticket/media/8/media_6977205ab9b045.41151873.mp4	2026-01-26 08:05:47	8	media/8/thumb_media_6977205ab9b045.41151873.mp4.jpg
10	media_6977205d0851d5.20867637.png	3d550e38dc2df1fc4689ec593c1f9a5f.png	image/png	812291	support-ticket/media/8/media_6977205d0851d5.20867637.png	2026-01-26 08:05:49	8	support-ticket/media/8/thumb_media_6977205d0851d5.20867637.png
11	media_697720697db608.54424521.png	28331df4ee1414d051cbf15874a77ec4.png	image/png	993536	support-ticket/media/8/media_697720697db608.54424521.png	2026-01-26 08:06:01	8	support-ticket/media/8/thumb_media_697720697db608.54424521.png
12	media_6977206d6ba8b2.00814747.png	4d6c5458bac865be545c2cf659846a4c.png	image/png	815108	support-ticket/media/8/media_6977206d6ba8b2.00814747.png	2026-01-26 08:06:05	8	support-ticket/media/8/thumb_media_6977206d6ba8b2.00814747.png
13	media_6977211e0b8834.84276412.jpg	ef0a944e-350d-4b76-ac12-67904339ac96.jpg	image/jpeg	184489	support-ticket/media/7/media_6977211e0b8834.84276412.jpg	2026-01-26 08:09:02	7	support-ticket/media/7/thumb_media_6977211e0b8834.84276412.jpg
14	media_6977211ea81160.96319986.png	Без названия.png	image/png	1238007	support-ticket/media/7/media_6977211ea81160.96319986.png	2026-01-26 08:09:02	7	support-ticket/media/7/thumb_media_6977211ea81160.96319986.png
15	media_69772128be9588.80434760.jpg	photo_5456239862462745720_y.jpg	image/jpeg	40343	support-ticket/media/7/media_69772128be9588.80434760.jpg	2026-01-26 08:09:12	7	support-ticket/media/7/thumb_media_69772128be9588.80434760.jpg
16	media_69772128b99680.43797894.jpg	photo_5456239862462745730_y.jpg	image/jpeg	82207	support-ticket/media/7/media_69772128b99680.43797894.jpg	2026-01-26 08:09:12	7	support-ticket/media/7/thumb_media_69772128b99680.43797894.jpg
17	media_69772128d607f3.22014089.jpg	photo_5456239862462745719_y.jpg	image/jpeg	63627	support-ticket/media/7/media_69772128d607f3.22014089.jpg	2026-01-26 08:09:13	7	support-ticket/media/7/thumb_media_69772128d607f3.22014089.jpg
18	media_69772129032690.31499111.jpg	photo_5456651651042184538_y.jpg	image/jpeg	163425	support-ticket/media/7/media_69772129032690.31499111.jpg	2026-01-26 08:09:13	7	support-ticket/media/7/thumb_media_69772129032690.31499111.jpg
19	media_6977212919c560.48775609.jpg	photo_5456651651042184537_y.jpg	image/jpeg	147151	support-ticket/media/7/media_6977212919c560.48775609.jpg	2026-01-26 08:09:13	7	support-ticket/media/7/thumb_media_6977212919c560.48775609.jpg
20	media_697721291d9ed7.41688042.jpg	photo_5456651651042184536_y.jpg	image/jpeg	146533	support-ticket/media/7/media_697721291d9ed7.41688042.jpg	2026-01-26 08:09:13	7	support-ticket/media/7/thumb_media_697721291d9ed7.41688042.jpg
21	media_69772f014876e4.77466345.webp	i (2).webp	image/webp	153622	support-ticket/media/10/media_69772f014876e4.77466345.webp	2026-01-26 09:08:17	10	support-ticket/media/10/thumb_media_69772f014876e4.77466345.webp
22	media_69772f01552be1.24864082.webp	i (1).webp	image/webp	129454	support-ticket/media/10/media_69772f01552be1.24864082.webp	2026-01-26 09:08:17	10	support-ticket/media/10/thumb_media_69772f01552be1.24864082.webp
23	media_69772f015f1323.79294270.webp	i.webp	image/webp	126744	support-ticket/media/10/media_69772f015f1323.79294270.webp	2026-01-26 09:08:17	10	support-ticket/media/10/thumb_media_69772f015f1323.79294270.webp
24	media_697733138e4103.11591467.webp	72f09eff-2b4c-4911-8f22-21df1c442f7b.webp	image/webp	76074	support-ticket/media/10/media_697733138e4103.11591467.webp	2026-01-26 09:25:39	10	support-ticket/media/10/thumb_media_697733138e4103.11591467.webp
25	media_6977331c89ab52.12760688.mp4	10814736108193.mp4	video/mp4	33290149	support-ticket/media/10/media_6977331c89ab52.12760688.mp4	2026-01-26 09:25:49	10	media/10/thumb_media_6977331c89ab52.12760688.mp4.jpg
26	media_6977370fe28329.78042065.jpg	5456357518796852610.jpg	image/jpeg	187142	support-ticket/media/9/media_6977370fe28329.78042065.jpg	2026-01-26 09:42:40	9	support-ticket/media/9/thumb_media_6977370fe28329.78042065.jpg
27	media_6979d19b1b3cc6.15724930.jpg	photo_5463332120483794104_y.jpg	image/jpeg	201237	support-ticket/media/14/media_6979d19b1b3cc6.15724930.jpg	2026-01-28 09:06:35	14	support-ticket/media/14/thumb_media_6979d19b1b3cc6.15724930.jpg
28	media_6979d19e86cb47.60313953.mp4	document_5463332120023832162.mp4	video/mp4	6520810	support-ticket/media/14/media_6979d19e86cb47.60313953.mp4	2026-01-28 09:06:40	14	media/14/thumb_media_6979d19e86cb47.60313953.mp4.jpg
29	media_6979ebfbddb671.61274437.jpg	photo_5463230282514238931_y (1).jpg	image/jpeg	66265	support-ticket/media/15/media_6979ebfbddb671.61274437.jpg	2026-01-28 10:59:08	15	support-ticket/media/15/thumb_media_6979ebfbddb671.61274437.jpg
30	media_6979ebfc30a5c7.74533842.jpg	photo_5463230282514239080_y.jpg	image/jpeg	98447	support-ticket/media/15/media_6979ebfc30a5c7.74533842.jpg	2026-01-28 10:59:08	15	support-ticket/media/15/thumb_media_6979ebfc30a5c7.74533842.jpg
31	media_6979ebfc626510.66201838.jpg	photo_5463230282514238928_y.jpg	image/jpeg	86388	support-ticket/media/15/media_6979ebfc626510.66201838.jpg	2026-01-28 10:59:08	15	support-ticket/media/15/thumb_media_6979ebfc626510.66201838.jpg
32	media_6979ebfc902304.53821375.jpg	photo_5463230282514238929_y (1).jpg	image/jpeg	93993	support-ticket/media/15/media_6979ebfc902304.53821375.jpg	2026-01-28 10:59:08	15	support-ticket/media/15/thumb_media_6979ebfc902304.53821375.jpg
33	media_6979ebfcbb47f1.11077062.jpg	photo_5463091005314764064_y.jpg	image/jpeg	56008	support-ticket/media/15/media_6979ebfcbb47f1.11077062.jpg	2026-01-28 10:59:08	15	support-ticket/media/15/thumb_media_6979ebfcbb47f1.11077062.jpg
34	media_6979ebfce9fac4.63457476.jpg	photo_5463091005314764067_y.jpg	image/jpeg	57347	support-ticket/media/15/media_6979ebfce9fac4.63457476.jpg	2026-01-28 10:59:09	15	support-ticket/media/15/thumb_media_6979ebfce9fac4.63457476.jpg
35	media_6979ec065c6b72.79146775.mp4	document_5463230282054275281.mp4	video/mp4	8166525	support-ticket/media/15/media_6979ec065c6b72.79146775.mp4	2026-01-28 10:59:19	15	media/15/thumb_media_6979ec065c6b72.79146775.mp4.jpg
36	media_6979ec0b6d4947.93879789.mp4	document_5463230282054275276.mp4	video/mp4	18043764	support-ticket/media/15/media_6979ec0b6d4947.93879789.mp4	2026-01-28 10:59:24	15	media/15/thumb_media_6979ec0b6d4947.93879789.mp4.jpg
37	media_6979ec0c96dd95.82610764.mp4	document_5463230282054275312.mp4	video/mp4	23713872	support-ticket/media/15/media_6979ec0c96dd95.82610764.mp4	2026-01-28 10:59:25	15	media/15/thumb_media_6979ec0c96dd95.82610764.mp4.jpg
38	media_697b0ceba02577.74349701.jpg	photo_2026-01-29_09-58-26.jpg	image/jpeg	53485	support-ticket/media/14/media_697b0ceba02577.74349701.jpg	2026-01-29 07:31:55	14	support-ticket/media/14/thumb_media_697b0ceba02577.74349701.jpg
39	media_697b0cefc11585.17021873.jpg	photo_5465588992653856337_y.jpg	image/jpeg	90709	support-ticket/media/14/media_697b0cefc11585.17021873.jpg	2026-01-29 07:32:00	14	support-ticket/media/14/thumb_media_697b0cefc11585.17021873.jpg
40	media_697b11f02da259.55490842.jpg	photo_5465588992653856509_y.jpg	image/jpeg	147119	support-ticket/media/14/media_697b11f02da259.55490842.jpg	2026-01-29 07:53:20	14	support-ticket/media/14/thumb_media_697b11f02da259.55490842.jpg
41	media_697b2122e7d972.10405729.jpg	Без имени1.jpg	image/jpeg	71194	support-ticket/media/17/media_697b2122e7d972.10405729.jpg	2026-01-29 08:58:11	17	support-ticket/media/17/thumb_media_697b2122e7d972.10405729.jpg
42	media_697b2122ef1eb5.86750155.jpg	Без имени.jpg	image/jpeg	160361	support-ticket/media/17/media_697b2122ef1eb5.86750155.jpg	2026-01-29 08:58:11	17	support-ticket/media/17/thumb_media_697b2122ef1eb5.86750155.jpg
43	media_697b2123040586.10016482.jpg	Без имени2.jpg	image/jpeg	148939	support-ticket/media/17/media_697b2123040586.10016482.jpg	2026-01-29 08:58:11	17	support-ticket/media/17/thumb_media_697b2123040586.10016482.jpg
44	media_697b2123081840.45677349.jpg	Без имени3.jpg	image/jpeg	188685	support-ticket/media/17/media_697b2123081840.45677349.jpg	2026-01-29 08:58:11	17	support-ticket/media/17/thumb_media_697b2123081840.45677349.jpg
45	media_697b21234d9889.68002302.jpg	Без имени11.jpg	image/jpeg	55619	support-ticket/media/17/media_697b21234d9889.68002302.jpg	2026-01-29 08:58:11	17	support-ticket/media/17/thumb_media_697b21234d9889.68002302.jpg
46	media_697b21233f19e1.40498477.jpg	Без имени5.jpg	image/jpeg	176169	support-ticket/media/17/media_697b21233f19e1.40498477.jpg	2026-01-29 08:58:11	17	support-ticket/media/17/thumb_media_697b21233f19e1.40498477.jpg
47	media_697b21234ceb88.61834350.jpg	Без имени4.jpg	image/jpeg	175746	support-ticket/media/17/media_697b21234ceb88.61834350.jpg	2026-01-29 08:58:11	17	support-ticket/media/17/thumb_media_697b21234ceb88.61834350.jpg
48	media_697b2474765ad3.43568459.jpg	photo_2026-01-26_07-28-02.jpg	image/jpeg	138561	support-ticket/media/18/media_697b2474765ad3.43568459.jpg	2026-01-29 09:12:20	18	support-ticket/media/18/thumb_media_697b2474765ad3.43568459.jpg
49	media_697b24747e4ea4.61859452.jpg	photo_2026-01-26_11-24-32.jpg	image/jpeg	156338	support-ticket/media/18/media_697b24747e4ea4.61859452.jpg	2026-01-29 09:12:20	18	support-ticket/media/18/thumb_media_697b24747e4ea4.61859452.jpg
50	media_697b2474813687.38065674.jpg	photo_2026-01-26_11-39-55.jpg	image/jpeg	140229	support-ticket/media/18/media_697b2474813687.38065674.jpg	2026-01-29 09:12:20	18	support-ticket/media/18/thumb_media_697b2474813687.38065674.jpg
51	media_697b247489cd06.70700307.jpg	Без имени1.jpg	image/jpeg	142099	support-ticket/media/18/media_697b247489cd06.70700307.jpg	2026-01-29 09:12:20	18	support-ticket/media/18/thumb_media_697b247489cd06.70700307.jpg
52	media_697b2474a65bb0.23997288.jpg	Без имени11.jpg	image/jpeg	181333	support-ticket/media/18/media_697b2474a65bb0.23997288.jpg	2026-01-29 09:12:20	18	support-ticket/media/18/thumb_media_697b2474a65bb0.23997288.jpg
53	media_697b2474b7b975.32665625.jpg	Без имени111.jpg	image/jpeg	189817	support-ticket/media/18/media_697b2474b7b975.32665625.jpg	2026-01-29 09:12:20	18	support-ticket/media/18/thumb_media_697b2474b7b975.32665625.jpg
54	media_697b2c64649264.65739330.jpg	IMAGE 2026-01-29 12:46:10.jpg	image/jpeg	84482	support-ticket/media/1/media_697b2c64649264.65739330.jpg	2026-01-29 09:46:12	1	support-ticket/media/1/thumb_media_697b2c64649264.65739330.jpg
55	media_697b2c6bad1f30.84381929.jpg	IMAGE 2026-01-29 12:46:18.jpg	image/jpeg	43539	support-ticket/media/1/media_697b2c6bad1f30.84381929.jpg	2026-01-29 09:46:19	1	support-ticket/media/1/thumb_media_697b2c6bad1f30.84381929.jpg
56	media_697c4401184c35.43569017.mp4	10698030189124.mp4	video/mp4	21943077	support-ticket/media/19/media_697c4401184c35.43569017.mp4	2026-01-30 05:39:14	19	media/19/thumb_media_697c4401184c35.43569017.mp4.jpg
57	media_697c4ceac15738.99462427.jpg	09c55b09-be2e-4faa-8903-7bbea87d1246.jpg	image/jpeg	151780	support-ticket/media/19/media_697c4ceac15738.99462427.jpg	2026-01-30 06:17:15	19	support-ticket/media/19/thumb_media_697c4ceac15738.99462427.jpg
58	media_697c4ceacf9668.10451891.jpg	cbd7f2d7-3431-47e7-b6be-0c4da6531a30.jpg	image/jpeg	178059	support-ticket/media/19/media_697c4ceacf9668.10451891.jpg	2026-01-30 06:17:15	19	support-ticket/media/19/thumb_media_697c4ceacf9668.10451891.jpg
59	media_697c4d6b7a0846.83360896.jpg	5467925798460263534.jpg	image/jpeg	73462	support-ticket/media/20/media_697c4d6b7a0846.83360896.jpg	2026-01-30 06:19:23	20	support-ticket/media/20/thumb_media_697c4d6b7a0846.83360896.jpg
60	media_697c6aff8b07f3.27398570.jpg	photo_5467899685059105060_y.jpg	image/jpeg	126994	support-ticket/media/21/media_697c6aff8b07f3.27398570.jpg	2026-01-30 08:25:35	21	support-ticket/media/21/thumb_media_697c6aff8b07f3.27398570.jpg
61	media_697c6affbbde09.83782736.jpg	photo_5467899685059105064_y.jpg	image/jpeg	133329	support-ticket/media/21/media_697c6affbbde09.83782736.jpg	2026-01-30 08:25:35	21	support-ticket/media/21/thumb_media_697c6affbbde09.83782736.jpg
63	media_697c807cab0273.42754942.mp4	document_5467899684599144075.mp4	video/mp4	39589370	support-ticket/media/21/media_697c807cab0273.42754942.mp4	2026-01-30 09:57:19	21	media/21/thumb_media_697c807cab0273.42754942.mp4.jpg
64	media_697cb91b3e1905.98980027.webp	i3.webp	image/webp	120958	support-ticket/media/23/media_697cb91b3e1905.98980027.webp	2026-01-30 13:58:51	23	support-ticket/media/23/thumb_media_697cb91b3e1905.98980027.webp
66	media_697cb91b3ec523.35163201.webp	iй.webp	image/webp	89780	support-ticket/media/23/media_697cb91b3ec523.35163201.webp	2026-01-30 13:58:51	23	support-ticket/media/23/thumb_media_697cb91b3ec523.35163201.webp
65	media_697cb91b419752.92669260.webp	i.webp	image/webp	61218	support-ticket/media/23/media_697cb91b419752.92669260.webp	2026-01-30 13:58:51	23	support-ticket/media/23/thumb_media_697cb91b419752.92669260.webp
67	media_698054ee528517.21186490.jpg	photo_5469749621962771766_y.jpg	image/jpeg	95745	support-ticket/media/25/media_698054ee528517.21186490.jpg	2026-02-02 07:40:30	25	support-ticket/media/25/thumb_media_698054ee528517.21186490.jpg
68	media_698054ee965010.80613342.jpg	photo_5469749621962771768_y.jpg	image/jpeg	123497	support-ticket/media/25/media_698054ee965010.80613342.jpg	2026-02-02 07:40:30	25	support-ticket/media/25/thumb_media_698054ee965010.80613342.jpg
69	media_698054eec30108.35545292.jpg	photo_5469749621962771770_y.jpg	image/jpeg	85514	support-ticket/media/25/media_698054eec30108.35545292.jpg	2026-02-02 07:40:30	25	support-ticket/media/25/thumb_media_698054eec30108.35545292.jpg
70	media_698054eee0bf47.76084859.jpg	photo_5469749621962771772_y.jpg	image/jpeg	72516	support-ticket/media/25/media_698054eee0bf47.76084859.jpg	2026-02-02 07:40:31	25	support-ticket/media/25/thumb_media_698054eee0bf47.76084859.jpg
71	media_698054ef0e3bb1.97688022.jpg	photo_5469749621962771767_y.jpg	image/jpeg	105943	support-ticket/media/25/media_698054ef0e3bb1.97688022.jpg	2026-02-02 07:40:31	25	support-ticket/media/25/thumb_media_698054ef0e3bb1.97688022.jpg
72	media_698054ef2e6718.32597302.jpg	photo_5469749621962771769_y.jpg	image/jpeg	96493	support-ticket/media/25/media_698054ef2e6718.32597302.jpg	2026-02-02 07:40:31	25	support-ticket/media/25/thumb_media_698054ef2e6718.32597302.jpg
73	media_698054ef4de231.39289820.jpg	photo_5469749621962771771_y.jpg	image/jpeg	82916	support-ticket/media/25/media_698054ef4de231.39289820.jpg	2026-02-02 07:40:31	25	support-ticket/media/25/thumb_media_698054ef4de231.39289820.jpg
74	media_6980b4aca38025.25553095.jpg	Без имени3.jpg	image/jpeg	126701	support-ticket/media/28/media_6980b4aca38025.25553095.jpg	2026-02-02 14:29:00	28	support-ticket/media/28/thumb_media_6980b4aca38025.25553095.jpg
75	media_6980b4aca7c021.35988198.jpg	программа работает.jpg	image/jpeg	84748	support-ticket/media/28/media_6980b4aca7c021.35988198.jpg	2026-02-02 14:29:00	28	support-ticket/media/28/thumb_media_6980b4aca7c021.35988198.jpg
76	media_6980b4acad2ad8.60133256.jpg	Без имени1.jpg	image/jpeg	142577	support-ticket/media/28/media_6980b4acad2ad8.60133256.jpg	2026-02-02 14:29:01	28	support-ticket/media/28/thumb_media_6980b4acad2ad8.60133256.jpg
77	media_6980b4acb3a944.07811816.jpg	Без имени.jpg	image/jpeg	198808	support-ticket/media/28/media_6980b4acb3a944.07811816.jpg	2026-02-02 14:29:01	28	support-ticket/media/28/thumb_media_6980b4acb3a944.07811816.jpg
78	media_6980b4ace59381.23487908.jpg	Без имени2.jpg	image/jpeg	74991	support-ticket/media/28/media_6980b4ace59381.23487908.jpg	2026-02-02 14:29:01	28	support-ticket/media/28/thumb_media_6980b4ace59381.23487908.jpg
79	media_6981853be21d59.49616606.jpg	ac963946-0426-4947-969d-2d0d20646765.jpg	image/jpeg	148048	support-ticket/media/29/media_6981853be21d59.49616606.jpg	2026-02-03 05:18:52	29	support-ticket/media/29/thumb_media_6981853be21d59.49616606.jpg
80	media_6981853c1f42b3.61642232.jpg	dd126901-3bf8-49f9-808f-4b0504c66744.jpg	image/jpeg	112830	support-ticket/media/29/media_6981853c1f42b3.61642232.jpg	2026-02-03 05:18:52	29	support-ticket/media/29/thumb_media_6981853c1f42b3.61642232.jpg
81	media_69818ea25a2f23.21919154.jpg	5192733255097060327.jpg	image/jpeg	190113	support-ticket/media/30/media_69818ea25a2f23.21919154.jpg	2026-02-03 05:58:58	30	support-ticket/media/30/thumb_media_69818ea25a2f23.21919154.jpg
82	media_69818ea2938532.29569929.MP4	IMG_2846.MP4	video/mp4	1169559	support-ticket/media/30/media_69818ea2938532.29569929.MP4	2026-02-03 05:59:00	30	media/30/thumb_media_69818ea2938532.29569929.MP4.jpg
83	media_69819271a36b21.24814193.jpg	5192733255097060407.jpg	image/jpeg	138794	support-ticket/media/30/media_69819271a36b21.24814193.jpg	2026-02-03 06:15:13	30	support-ticket/media/30/thumb_media_69819271a36b21.24814193.jpg
84	media_698193651f22f5.68169323.jpg	5192733255097060420.jpg	image/jpeg	72341	support-ticket/media/30/media_698193651f22f5.68169323.jpg	2026-02-03 06:19:17	30	support-ticket/media/30/thumb_media_698193651f22f5.68169323.jpg
85	media_69819db9aded42.55635075.mp4	document_5192747148856299008.mp4	video/mp4	9073872	support-ticket/media/30/media_69819db9aded42.55635075.mp4	2026-02-03 07:03:22	30	media/30/thumb_media_69819db9aded42.55635075.mp4.jpg
86	media_6982dc45cd3d42.99564478.jpg	8d91b8b738239b354 (1).jpg	image/jpeg	83881	support-ticket/media/39/media_6982dc45cd3d42.99564478.jpg	2026-02-04 05:42:29	39	support-ticket/media/39/thumb_media_6982dc45cd3d42.99564478.jpg
88	media_69830ca57f3189.97711927.jpg	Без имени1.jpg	image/jpeg	91024	support-ticket/media/40/media_69830ca57f3189.97711927.jpg	2026-02-04 09:08:53	40	support-ticket/media/40/thumb_media_69830ca57f3189.97711927.jpg
87	media_69830ca58487a7.61114202.jpg	Без имени.jpg	image/jpeg	99771	support-ticket/media/40/media_69830ca58487a7.61114202.jpg	2026-02-04 09:08:53	40	support-ticket/media/40/thumb_media_69830ca58487a7.61114202.jpg
89	media_698317a7782653.90161370.jpg	photo_2026-02-04_12-55-31.jpg	image/jpeg	65349	support-ticket/media/41/media_698317a7782653.90161370.jpg	2026-02-04 09:55:51	41	support-ticket/media/41/thumb_media_698317a7782653.90161370.jpg
90	media_698317a79c3494.28375036.jpg	photo_2026-02-04_12-55-28.jpg	image/jpeg	167188	support-ticket/media/41/media_698317a79c3494.28375036.jpg	2026-02-04 09:55:51	41	support-ticket/media/41/thumb_media_698317a79c3494.28375036.jpg
91	media_698317a7c118c6.58703169.jpg	photo_2026-02-04_12-55-24.jpg	image/jpeg	75283	support-ticket/media/41/media_698317a7c118c6.58703169.jpg	2026-02-04 09:55:51	41	support-ticket/media/41/thumb_media_698317a7c118c6.58703169.jpg
92	media_698317a7e4fbc5.55666783.MOV	IMG_0108.MOV	video/mp4	905287	support-ticket/media/41/media_698317a7e4fbc5.55666783.MOV	2026-02-04 09:55:52	41	media/41/thumb_media_698317a7e4fbc5.55666783.MOV.jpg
93	media_69831cd0e65866.34114531.jpg	0362a9e3-a969-440e-aba9-226c12c16349.jpg	image/jpeg	65587	support-ticket/media/41/media_69831cd0e65866.34114531.jpg	2026-02-04 10:17:53	41	support-ticket/media/41/thumb_media_69831cd0e65866.34114531.jpg
94	media_69831cd11ce821.97081408.jpg	6fd4e5f8-1a85-494a-b7d4-6b7c2719565c.jpg	image/jpeg	139608	support-ticket/media/41/media_69831cd11ce821.97081408.jpg	2026-02-04 10:17:53	41	support-ticket/media/41/thumb_media_69831cd11ce821.97081408.jpg
95	media_69831cd12b26e8.38840072.jpg	3c97d1e0-1781-440d-9eeb-025adf175932.jpg	image/jpeg	97722	support-ticket/media/41/media_69831cd12b26e8.38840072.jpg	2026-02-04 10:17:53	41	support-ticket/media/41/thumb_media_69831cd12b26e8.38840072.jpg
96	media_69831cd147c4d2.99857578.jpg	9ad04ab1-c504-4d3f-a679-95b0bdbc0ec8.jpg	image/jpeg	130994	support-ticket/media/41/media_69831cd147c4d2.99857578.jpg	2026-02-04 10:17:53	41	support-ticket/media/41/thumb_media_69831cd147c4d2.99857578.jpg
97	media_69831cd1448643.66917077.jpg	9322e375-3473-4ded-badf-ef3d54ba1a24.jpg	image/jpeg	99627	support-ticket/media/41/media_69831cd1448643.66917077.jpg	2026-02-04 10:17:53	41	support-ticket/media/41/thumb_media_69831cd1448643.66917077.jpg
98	media_698327314e1d69.68864016.jpg	5 (1).jpg	image/jpeg	186301	support-ticket/media/42/media_698327314e1d69.68864016.jpg	2026-02-04 11:02:09	42	support-ticket/media/42/thumb_media_698327314e1d69.68864016.jpg
99	media_69832828dd7282.70877088.jpg	5 (2).jpg	image/jpeg	150895	support-ticket/media/43/media_69832828dd7282.70877088.jpg	2026-02-04 11:06:17	43	support-ticket/media/43/thumb_media_69832828dd7282.70877088.jpg
100	media_69832829224460.55643888.jpg	5 (3).jpg	image/jpeg	155069	support-ticket/media/43/media_69832829224460.55643888.jpg	2026-02-04 11:06:17	43	support-ticket/media/43/thumb_media_69832829224460.55643888.jpg
101	media_698328294d01d9.99883834.jpg	5 (4).jpg	image/jpeg	157679	support-ticket/media/43/media_698328294d01d9.99883834.jpg	2026-02-04 11:06:17	43	support-ticket/media/43/thumb_media_698328294d01d9.99883834.jpg
102	media_6983282975eca7.84456061.jpg	5 (5).jpg	image/jpeg	147816	support-ticket/media/43/media_6983282975eca7.84456061.jpg	2026-02-04 11:06:17	43	support-ticket/media/43/thumb_media_6983282975eca7.84456061.jpg
103	media_698339615072b9.36857135.jpg	шильдик.jpg	image/jpeg	243410	support-ticket/media/44/media_698339615072b9.36857135.jpg	2026-02-04 12:19:45	44	support-ticket/media/44/thumb_media_698339615072b9.36857135.jpg
104	media_698422888d2a55.96596367.jpg	9256fdd7-e1db-4c56-ac2c-c2d2cbc8b36e.jpg	image/jpeg	87441	support-ticket/media/41/media_698422888d2a55.96596367.jpg	2026-02-05 04:54:32	41	support-ticket/media/41/thumb_media_698422888d2a55.96596367.jpg
105	media_69842288b03742.88664930.jpg	af37b500-e03d-4ec6-a901-5f9dbfc3f606.jpg	image/jpeg	72161	support-ticket/media/41/media_69842288b03742.88664930.jpg	2026-02-05 04:54:32	41	support-ticket/media/41/thumb_media_69842288b03742.88664930.jpg
106	media_69842288ab1dc8.72329520.jpg	d451d5b3-39b8-43dd-b4ec-3c049f007c0e.jpg	image/jpeg	117965	support-ticket/media/41/media_69842288ab1dc8.72329520.jpg	2026-02-05 04:54:32	41	support-ticket/media/41/thumb_media_69842288ab1dc8.72329520.jpg
107	media_698427c0471115.81709996.jpg	9659433a-824c-4db3-8998-d5a649d74b62.jpg	image/jpeg	196611	support-ticket/media/41/media_698427c0471115.81709996.jpg	2026-02-05 05:16:48	41	support-ticket/media/41/thumb_media_698427c0471115.81709996.jpg
108	media_69844cb3f0b1b2.59616596.jpg	17a80fac-f605-4a29-9aba-4f44093f7e3a.jpg	image/jpeg	186301	support-ticket/media/43/media_69844cb3f0b1b2.59616596.jpg	2026-02-05 07:54:28	43	support-ticket/media/43/thumb_media_69844cb3f0b1b2.59616596.jpg
109	media_6984528382e925.64327738.jpg	photo_5197454794249932859_y.jpg	image/jpeg	182535	support-ticket/media/45/media_6984528382e925.64327738.jpg	2026-02-05 08:19:15	45	support-ticket/media/45/thumb_media_6984528382e925.64327738.jpg
110	media_69845283ad8724.37089881.jpg	photo_5197454794249932857_y.jpg	image/jpeg	129694	support-ticket/media/45/media_69845283ad8724.37089881.jpg	2026-02-05 08:19:15	45	support-ticket/media/45/thumb_media_69845283ad8724.37089881.jpg
111	media_69845283d4e457.56983363.jpg	photo_5197454794249932855_y.jpg	image/jpeg	158447	support-ticket/media/45/media_69845283d4e457.56983363.jpg	2026-02-05 08:19:15	45	support-ticket/media/45/thumb_media_69845283d4e457.56983363.jpg
112	media_698452840682c7.81707589.jpg	photo_5197454794249932854_y.jpg	image/jpeg	118345	support-ticket/media/45/media_698452840682c7.81707589.jpg	2026-02-05 08:19:16	45	support-ticket/media/45/thumb_media_698452840682c7.81707589.jpg
113	media_69845340e7e080.97649120.jpg	photo_5197454794249932859_y.jpg	image/jpeg	182535	support-ticket/media/46/media_69845340e7e080.97649120.jpg	2026-02-05 08:22:25	46	support-ticket/media/46/thumb_media_69845340e7e080.97649120.jpg
114	media_698453412df620.88179598.jpg	photo_5197454794249932857_y.jpg	image/jpeg	129694	support-ticket/media/46/media_698453412df620.88179598.jpg	2026-02-05 08:22:25	46	support-ticket/media/46/thumb_media_698453412df620.88179598.jpg
115	media_6984534153d804.71431205.jpg	photo_5197454794249932855_y.jpg	image/jpeg	158447	support-ticket/media/46/media_6984534153d804.71431205.jpg	2026-02-05 08:22:25	46	support-ticket/media/46/thumb_media_6984534153d804.71431205.jpg
116	media_69845341780ad2.96240901.jpg	photo_5197454794249932854_y.jpg	image/jpeg	118345	support-ticket/media/46/media_69845341780ad2.96240901.jpg	2026-02-05 08:22:25	46	support-ticket/media/46/thumb_media_69845341780ad2.96240901.jpg
117	media_6984536344d191.04694371.jpg	4963b210-0d48-407b-a398-4b636215d09e (1).jpg	image/jpeg	94866	support-ticket/media/46/media_6984536344d191.04694371.jpg	2026-02-05 08:22:59	46	support-ticket/media/46/thumb_media_6984536344d191.04694371.jpg
118	media_6985868509f4c6.44028574.jpg	photo_1_2026-02-06_09-13-14.jpg	image/jpeg	129892	support-ticket/media/47/media_6985868509f4c6.44028574.jpg	2026-02-06 06:13:25	47	support-ticket/media/47/thumb_media_6985868509f4c6.44028574.jpg
119	media_698586853b1502.79558542.jpg	photo_2_2026-02-06_09-13-14.jpg	image/jpeg	114853	support-ticket/media/47/media_698586853b1502.79558542.jpg	2026-02-06 06:13:25	47	support-ticket/media/47/thumb_media_698586853b1502.79558542.jpg
120	media_69858685604a24.01438404.jpg	photo_3_2026-02-06_09-13-14.jpg	image/jpeg	127457	support-ticket/media/47/media_69858685604a24.01438404.jpg	2026-02-06 06:13:25	47	support-ticket/media/47/thumb_media_69858685604a24.01438404.jpg
121	media_69858732df18c0.61560490.mp4	VID_20260204_130529.mp4	video/mp4	2228224	support-ticket/media/48/media_69858732df18c0.61560490.mp4	2026-02-06 06:16:19	48	\N
122	media_69858733b4c8d4.81204751.jpg	5199539962217434400.jpg	image/jpeg	162175	support-ticket/media/48/media_69858733b4c8d4.81204751.jpg	2026-02-06 06:16:19	48	support-ticket/media/48/thumb_media_69858733b4c8d4.81204751.jpg
123	media_69858733d70554.94997907.jpg	5199539962217434399.jpg	image/jpeg	152252	support-ticket/media/48/media_69858733d70554.94997907.jpg	2026-02-06 06:16:20	48	support-ticket/media/48/thumb_media_69858733d70554.94997907.jpg
124	media_69858734100983.34692772.jpg	5199539962217434401.jpg	image/jpeg	136057	support-ticket/media/48/media_69858734100983.34692772.jpg	2026-02-06 06:16:20	48	support-ticket/media/48/thumb_media_69858734100983.34692772.jpg
125	media_69858734343871.21745755.jpg	decbb26447a367b7c.jpg	image/jpeg	136057	support-ticket/media/48/media_69858734343871.21745755.jpg	2026-02-06 06:16:20	48	support-ticket/media/48/thumb_media_69858734343871.21745755.jpg
126	media_698587345a9b01.88971759.jpg	b34df683af79a54b6.jpg	image/jpeg	162175	support-ticket/media/48/media_698587345a9b01.88971759.jpg	2026-02-06 06:16:20	48	support-ticket/media/48/thumb_media_698587345a9b01.88971759.jpg
127	media_698587348e8202.67584633.jpg	79469a8a39fd25ba8.jpg	image/jpeg	152252	support-ticket/media/48/media_698587348e8202.67584633.jpg	2026-02-06 06:16:20	48	support-ticket/media/48/thumb_media_698587348e8202.67584633.jpg
128	media_698587b5d424f2.77016643.mp4	VID_20260204_131216 (3).mp4	video/mp4	15859712	support-ticket/media/48/media_698587b5d424f2.77016643.mp4	2026-02-06 06:18:30	48	media/48/thumb_media_698587b5d424f2.77016643.mp4.jpg
129	media_6985a0aa69bd96.83466296.MOV	IMG_8064.MOV	video/mp4	10391107	support-ticket/media/49/media_6985a0aa69bd96.83466296.MOV	2026-02-06 08:04:59	49	media/49/thumb_media_6985a0aa69bd96.83466296.MOV.jpg
130	media_6985a0b9110e98.90094682.jpg	photo_1_2026-02-06_11-05-06.jpg	image/jpeg	176290	support-ticket/media/49/media_6985a0b9110e98.90094682.jpg	2026-02-06 08:05:13	49	support-ticket/media/49/thumb_media_6985a0b9110e98.90094682.jpg
131	media_6985a0b91a5065.17732617.jpg	photo_2_2026-02-06_11-05-06.jpg	image/jpeg	165462	support-ticket/media/49/media_6985a0b91a5065.17732617.jpg	2026-02-06 08:05:13	49	support-ticket/media/49/thumb_media_6985a0b91a5065.17732617.jpg
132	media_6985d9071f9bc7.27420825.png	Без названия.png	image/png	673489	support-ticket/media/33/media_6985d9071f9bc7.27420825.png	2026-02-06 12:05:27	33	support-ticket/media/33/thumb_media_6985d9071f9bc7.27420825.png
133	media_6985d9080bb626.29835022.png	Image_20260203153529_3_86.png	image/png	815108	support-ticket/media/33/media_6985d9080bb626.29835022.png	2026-02-06 12:05:28	33	support-ticket/media/33/thumb_media_6985d9080bb626.29835022.png
134	media_6985d90eb69ca0.96044094.mp4	document_5199509235561437434 (1).mp4	video/mp4	2625960	support-ticket/media/33/media_6985d90eb69ca0.96044094.mp4	2026-02-06 12:05:35	33	media/33/thumb_media_6985d90eb69ca0.96044094.mp4.jpg
135	media_6985e4112df142.88999634.jpg	5201918300357594195.jpg	image/jpeg	139648	support-ticket/media/49/media_6985e4112df142.88999634.jpg	2026-02-06 12:52:33	49	support-ticket/media/49/thumb_media_6985e4112df142.88999634.jpg
136	media_6985e411381d67.68580841.jpg	5201918300357594220.jpg	image/jpeg	113384	support-ticket/media/49/media_6985e411381d67.68580841.jpg	2026-02-06 12:52:33	49	support-ticket/media/49/thumb_media_6985e411381d67.68580841.jpg
137	media_6985e4114560c2.46751769.jpg	5201828630030388201.jpg	image/jpeg	217989	support-ticket/media/49/media_6985e4114560c2.46751769.jpg	2026-02-06 12:52:33	49	support-ticket/media/49/thumb_media_6985e4114560c2.46751769.jpg
138	media_6985e41178a001.50614056.jpg	photo_2026-02-06_15-13-36.jpg	image/jpeg	218147	support-ticket/media/49/media_6985e41178a001.50614056.jpg	2026-02-06 12:52:33	49	support-ticket/media/49/thumb_media_6985e41178a001.50614056.jpg
139	media_6985e411778db4.49432117.jpg	photo_2026-02-06_15-13-38.jpg	image/jpeg	240557	support-ticket/media/49/media_6985e411778db4.49432117.jpg	2026-02-06 12:52:33	49	support-ticket/media/49/thumb_media_6985e411778db4.49432117.jpg
140	media_6985e411942936.96365878.mp4	video(1).mp4	video/mp4	5404724	support-ticket/media/49/media_6985e411942936.96365878.mp4	2026-02-06 12:52:34	49	media/49/thumb_media_6985e411942936.96365878.mp4.jpg
141	media_6985e8b98276a7.90799715.jpg	скорость.jpg	image/jpeg	174902	support-ticket/media/34/media_6985e8b98276a7.90799715.jpg	2026-02-06 13:12:25	34	support-ticket/media/34/thumb_media_6985e8b98276a7.90799715.jpg
142	media_6985ec4e6469a8.63257278.jpg	photo_2026-02-06_16-21-59.jpg	image/jpeg	129288	support-ticket/media/53/media_6985ec4e6469a8.63257278.jpg	2026-02-06 13:27:42	53	support-ticket/media/53/thumb_media_6985ec4e6469a8.63257278.jpg
143	media_6985ec4e79f3e9.56102062.jpg	photo_2026-02-06_16-22-36.jpg	image/jpeg	115894	support-ticket/media/53/media_6985ec4e79f3e9.56102062.jpg	2026-02-06 13:27:42	53	support-ticket/media/53/thumb_media_6985ec4e79f3e9.56102062.jpg
144	media_6985ec4e703087.97484930.jpg	photo_2026-02-06_16-22-18.jpg	image/jpeg	108052	support-ticket/media/53/media_6985ec4e703087.97484930.jpg	2026-02-06 13:27:42	53	support-ticket/media/53/thumb_media_6985ec4e703087.97484930.jpg
145	media_6985ec4e796cb6.59646750.jpg	photo_2026-02-06_16-22-23.jpg	image/jpeg	124295	support-ticket/media/53/media_6985ec4e796cb6.59646750.jpg	2026-02-06 13:27:42	53	support-ticket/media/53/thumb_media_6985ec4e796cb6.59646750.jpg
146	media_6985ec4f612e77.67712112.mp4	video_2026-02-06_16-22-41.mp4	video/mp4	14554885	support-ticket/media/53/media_6985ec4f612e77.67712112.mp4	2026-02-06 13:27:44	53	media/53/thumb_media_6985ec4f612e77.67712112.mp4.jpg
147	media_6988a7e575add5.38394637.pdf	КС Выяснение подробностей (1) (1).pdf	application/pdf	196961	support-ticket/media/1/media_6988a7e575add5.38394637.pdf	2026-02-08 15:12:39	1	support-ticket/media/1/thumb_media_6988a7e575add5.38394637.jpg
148	media_6988a7e52778c9.63095790.docx	_Общие_характеристики_для_всех_полей.docx	application/vnd.openxmlformats-officedocument.wordprocessingml.document	24893	support-ticket/media/1/media_6988a7e52778c9.63095790.docx	2026-02-08 15:12:43	1	support-ticket/media/1/thumb_media_6988a7e52778c9.63095790.jpg
149	media_698abc09d4d1e5.30750868.webp	i (4).webp	image/webp	59892	support-ticket/media/55/media_698abc09d4d1e5.30750868.webp	2026-02-10 05:03:06	55	support-ticket/media/55/thumb_media_698abc09d4d1e5.30750868.webp
150	media_698abc09d8fde2.19953395.webp	i (3).webp	image/webp	53188	support-ticket/media/55/media_698abc09d8fde2.19953395.webp	2026-02-10 05:03:06	55	support-ticket/media/55/thumb_media_698abc09d8fde2.19953395.webp
151	media_698abc0a0018a0.22423737.webp	i (2).webp	image/webp	152676	support-ticket/media/55/media_698abc0a0018a0.22423737.webp	2026-02-10 05:03:06	55	support-ticket/media/55/thumb_media_698abc0a0018a0.22423737.webp
152	media_698abc0a1d4d02.74052199.webp	i.webp	image/webp	134364	support-ticket/media/55/media_698abc0a1d4d02.74052199.webp	2026-02-10 05:03:06	55	support-ticket/media/55/thumb_media_698abc0a1d4d02.74052199.webp
153	media_698abc0a06d8d3.62655508.webp	i (1).webp	image/webp	123254	support-ticket/media/55/media_698abc0a06d8d3.62655508.webp	2026-02-10 05:03:06	55	support-ticket/media/55/thumb_media_698abc0a06d8d3.62655508.webp
154	media_698abc1503f3a6.45299420.mp4	11052052908591.mp4	video/mp4	8397940	support-ticket/media/55/media_698abc1503f3a6.45299420.mp4	2026-02-10 05:03:18	55	media/55/thumb_media_698abc1503f3a6.45299420.mp4.jpg
155	media_698abc14ea2139.70209639.mp4	11043796224559.mp4	video/mp4	7101430	support-ticket/media/55/media_698abc14ea2139.70209639.mp4	2026-02-10 05:03:18	55	media/55/thumb_media_698abc14ea2139.70209639.mp4.jpg
156	media_698c4b72104a84.08234205.jpg	942bbaa38f3901ef7.jpg	image/jpeg	125455	support-ticket/media/60/media_698c4b72104a84.08234205.jpg	2026-02-11 09:27:14	60	support-ticket/media/60/thumb_media_698c4b72104a84.08234205.jpg
157	media_698c4b72189db7.58274469.jpg	7858f2c81b14b04f9.jpg	image/jpeg	182616	support-ticket/media/60/media_698c4b72189db7.58274469.jpg	2026-02-11 09:27:14	60	support-ticket/media/60/thumb_media_698c4b72189db7.58274469.jpg
158	media_698c4b721edc78.90026024.jpg	3ad7673ad03b2f242.jpg	image/jpeg	164880	support-ticket/media/60/media_698c4b721edc78.90026024.jpg	2026-02-11 09:27:14	60	support-ticket/media/60/thumb_media_698c4b721edc78.90026024.jpg
159	media_698dd2e38ff213.02884711.mp4	11130009291433.mp4	video/mp4	23032004	support-ticket/media/62/media_698dd2e38ff213.02884711.mp4	2026-02-12 13:17:25	62	media/62/thumb_media_698dd2e38ff213.02884711.mp4.jpg
160	media_698dd2ed4c7e79.36731112.mp4	11129898076841.mp4	video/mp4	54504723	support-ticket/media/62/media_698dd2ed4c7e79.36731112.mp4	2026-02-12 13:17:35	62	media/62/thumb_media_698dd2ed4c7e79.36731112.mp4.jpg
161	media_698dd2f001aa12.59099941.webp	4sxctZzA.webp	image/webp	164590	support-ticket/media/62/media_698dd2f001aa12.59099941.webp	2026-02-12 13:17:36	62	support-ticket/media/62/thumb_media_698dd2f001aa12.59099941.webp
162	media_698dd7b4cd2546.51360757.jpg	Без имени.jpg	image/jpeg	90041	support-ticket/media/62/media_698dd7b4cd2546.51360757.jpg	2026-02-12 13:37:56	62	support-ticket/media/62/thumb_media_698dd7b4cd2546.51360757.jpg
163	media_698dd7b4d45e88.85194271.jpg	Без имени2.jpg	image/jpeg	95440	support-ticket/media/62/media_698dd7b4d45e88.85194271.jpg	2026-02-12 13:37:57	62	support-ticket/media/62/thumb_media_698dd7b4d45e88.85194271.jpg
164	media_698ed33379fe95.62292580.webp	444.webp	image/webp	114942	support-ticket/media/62/media_698ed33379fe95.62292580.webp	2026-02-13 07:30:59	62	support-ticket/media/62/thumb_media_698ed33379fe95.62292580.webp
165	media_698ed333788667.26319611.webp	33.webp	image/webp	241730	support-ticket/media/62/media_698ed333788667.26319611.webp	2026-02-13 07:30:59	62	support-ticket/media/62/thumb_media_698ed333788667.26319611.webp
166	media_698ed77cbd97a8.73628223.webp	4.webp	image/webp	145370	support-ticket/media/57/media_698ed77cbd97a8.73628223.webp	2026-02-13 07:49:17	57	support-ticket/media/57/thumb_media_698ed77cbd97a8.73628223.webp
167	media_698ed77ccbaff8.24782151.webp	2.webp	image/webp	80106	support-ticket/media/57/media_698ed77ccbaff8.24782151.webp	2026-02-13 07:49:17	57	support-ticket/media/57/thumb_media_698ed77ccbaff8.24782151.webp
168	media_698ed77cebc2d8.99711227.webp	1.webp	image/webp	56554	support-ticket/media/57/media_698ed77cebc2d8.99711227.webp	2026-02-13 07:49:17	57	support-ticket/media/57/thumb_media_698ed77cebc2d8.99711227.webp
169	media_698ed77d182444.21322963.webp	3.webp	image/webp	145746	support-ticket/media/57/media_698ed77d182444.21322963.webp	2026-02-13 07:49:17	57	support-ticket/media/57/thumb_media_698ed77d182444.21322963.webp
170	media_698ed77ddb6034.15714614.mp4	11294180248194.mp4	video/mp4	8757314	support-ticket/media/57/media_698ed77ddb6034.15714614.mp4	2026-02-13 07:49:18	57	media/57/thumb_media_698ed77ddb6034.15714614.mp4.jpg
171	media_698ee411a66117.40243907.webp	i.webp	image/webp	253964	support-ticket/media/61/media_698ee411a66117.40243907.webp	2026-02-13 08:42:57	61	support-ticket/media/61/thumb_media_698ee411a66117.40243907.webp
172	media_698f160847faf0.51651913.mp4	07e72cb20f07e1330.mp4	video/mp4	9516562	support-ticket/media/66/media_698f160847faf0.51651913.mp4	2026-02-13 12:16:08	66	media/66/thumb_media_698f160847faf0.51651913.mp4.jpg
173	media_6992be323c6b41.46122968.jpg	photo_2025-12-23_15-54-46.jpg	image/jpeg	302425	support-ticket/media/68/media_6992be323c6b41.46122968.jpg	2026-02-16 06:50:26	68	support-ticket/media/68/thumb_media_6992be323c6b41.46122968.jpg
174	media_6992cc21c56e03.70607698.mp4	document_5228698395945768317.mp4	video/mp4	1706429	support-ticket/media/69/media_6992cc21c56e03.70607698.mp4	2026-02-16 07:49:54	69	media/69/thumb_media_6992cc21c56e03.70607698.mp4.jpg
175	media_6992cc224e0039.86282407.jpg	photo_5228698396405732975_y.jpg	image/jpeg	156647	support-ticket/media/69/media_6992cc224e0039.86282407.jpg	2026-02-16 07:49:54	69	support-ticket/media/69/thumb_media_6992cc224e0039.86282407.jpg
176	media_6992cc228a5a75.84803635.mp4	document_5228698395945768247.mp4	video/mp4	2488656	support-ticket/media/69/media_6992cc228a5a75.84803635.mp4	2026-02-16 07:49:55	69	media/69/thumb_media_6992cc228a5a75.84803635.mp4.jpg
177	media_6992cc232462b9.68414417.mp4	document_5228698395945768252.mp4	video/mp4	2499218	support-ticket/media/69/media_6992cc232462b9.68414417.mp4	2026-02-16 07:49:55	69	media/69/thumb_media_6992cc232462b9.68414417.mp4.jpg
178	media_6992cc23aeda95.47493799.mp4	document_5228698395945768251.mp4	video/mp4	1782673	support-ticket/media/69/media_6992cc23aeda95.47493799.mp4	2026-02-16 07:49:56	69	media/69/thumb_media_6992cc23aeda95.47493799.mp4.jpg
179	media_6992eb1a1bcd55.30113189.jpg	3.jpg	image/jpeg	98857	support-ticket/media/66/media_6992eb1a1bcd55.30113189.jpg	2026-02-16 10:02:02	66	support-ticket/media/66/thumb_media_6992eb1a1bcd55.30113189.jpg
180	media_6992eb1a170e93.54404493.jpg	4.jpg	image/jpeg	80500	support-ticket/media/66/media_6992eb1a170e93.54404493.jpg	2026-02-16 10:02:02	66	support-ticket/media/66/thumb_media_6992eb1a170e93.54404493.jpg
181	media_6992eb1a259640.30735299.jpg	1.jpg	image/jpeg	108470	support-ticket/media/66/media_6992eb1a259640.30735299.jpg	2026-02-16 10:02:02	66	support-ticket/media/66/thumb_media_6992eb1a259640.30735299.jpg
182	media_6992eb1a1653e9.55974760.jpg	2.jpg	image/jpeg	119719	support-ticket/media/66/media_6992eb1a1653e9.55974760.jpg	2026-02-16 10:02:02	66	support-ticket/media/66/thumb_media_6992eb1a1653e9.55974760.jpg
183	media_6992eb1a74e2e7.86518411.jpg	5.jpg	image/jpeg	99661	support-ticket/media/66/media_6992eb1a74e2e7.86518411.jpg	2026-02-16 10:02:02	66	support-ticket/media/66/thumb_media_6992eb1a74e2e7.86518411.jpg
184	media_6992eb1a7d6883.91270253.jpg	7.jpg	image/jpeg	148799	support-ticket/media/66/media_6992eb1a7d6883.91270253.jpg	2026-02-16 10:02:02	66	support-ticket/media/66/thumb_media_6992eb1a7d6883.91270253.jpg
186	media_6992eb1a7c9ed3.23293002.jpg	8.jpg	image/jpeg	164462	support-ticket/media/66/media_6992eb1a7c9ed3.23293002.jpg	2026-02-16 10:02:02	66	support-ticket/media/66/thumb_media_6992eb1a7c9ed3.23293002.jpg
185	media_6992eb1a8643c4.04512075.jpg	6.jpg	image/jpeg	175759	support-ticket/media/66/media_6992eb1a8643c4.04512075.jpg	2026-02-16 10:02:02	66	support-ticket/media/66/thumb_media_6992eb1a8643c4.04512075.jpg
187	media_6992eb54de32b0.21550862.jpg	f57d6c8936d97c76f.jpg	image/jpeg	81604	support-ticket/media/70/media_6992eb54de32b0.21550862.jpg	2026-02-16 10:03:01	70	support-ticket/media/70/thumb_media_6992eb54de32b0.21550862.jpg
188	media_6992efe1143ae5.76149415.jpg	photo_2026-02-16_13-22-13.jpg	image/jpeg	130538	support-ticket/media/67/media_6992efe1143ae5.76149415.jpg	2026-02-16 10:22:25	67	support-ticket/media/67/thumb_media_6992efe1143ae5.76149415.jpg
189	media_6992efe11d8a36.78407752.jpg	photo_2026-02-16_13-22-09.jpg	image/jpeg	119873	support-ticket/media/67/media_6992efe11d8a36.78407752.jpg	2026-02-16 10:22:25	67	support-ticket/media/67/thumb_media_6992efe11d8a36.78407752.jpg
190	media_6992efe142b128.46487306.jpg	photo_2026-02-16_08-02-59.jpg	image/jpeg	70841	support-ticket/media/67/media_6992efe142b128.46487306.jpg	2026-02-16 10:22:25	67	support-ticket/media/67/thumb_media_6992efe142b128.46487306.jpg
191	media_6992efe1406113.95610304.jpg	photo_2026-02-16_07-05-06.jpg	image/jpeg	80504	support-ticket/media/67/media_6992efe1406113.95610304.jpg	2026-02-16 10:22:25	67	support-ticket/media/67/thumb_media_6992efe1406113.95610304.jpg
192	media_6992efe14281b2.71358098.jpg	photo_2026-02-16_12-22-56.jpg	image/jpeg	135524	support-ticket/media/67/media_6992efe14281b2.71358098.jpg	2026-02-16 10:22:25	67	support-ticket/media/67/thumb_media_6992efe14281b2.71358098.jpg
193	media_6992f1abec5e40.01218628.jpg	IMG_20260216_131121.jpg	image/jpeg	3982001	support-ticket/media/70/media_6992f1abec5e40.01218628.jpg	2026-02-16 10:30:04	70	support-ticket/media/70/thumb_media_6992f1abec5e40.01218628.jpg
195	media_6994371856ed33.10564631.pdf	MetalTec HBC 090_2500 РЭ ред 2022 08.pdf	application/pdf	2965218	support-ticket/media/71/media_6994371856ed33.10564631.pdf	2026-02-17 09:38:35	71	support-ticket/media/71/thumb_media_6994371856ed33.10564631.jpg
194	media_69943718712720.92244399.pdf	Стойка ESA 630_рус.pdf	application/pdf	5471854	support-ticket/media/71/media_69943718712720.92244399.pdf	2026-02-17 09:38:35	71	support-ticket/media/71/thumb_media_69943718712720.92244399.jpg
196	media_69943eca195247.95705214.jpg	photo_2026-02-17_13-04-56.jpg	image/jpeg	21762	support-ticket/media/71/media_69943eca195247.95705214.jpg	2026-02-17 10:11:22	71	support-ticket/media/71/thumb_media_69943eca195247.95705214.jpg
197	media_699449ce2f1e74.77210217.jpg	5233193126925636357.jpg	image/jpeg	95617	support-ticket/media/72/media_699449ce2f1e74.77210217.jpg	2026-02-17 10:58:22	72	support-ticket/media/72/thumb_media_699449ce2f1e74.77210217.jpg
198	media_6995c6e776bbf1.57174934.jpg	photo_2026-02-18_17-03-29.jpg	image/jpeg	58363	support-ticket/media/71/media_6995c6e776bbf1.57174934.jpg	2026-02-18 14:04:23	71	support-ticket/media/71/thumb_media_6995c6e776bbf1.57174934.jpg
199	media_69969e60e80d42.67565666.webp	i (2).webp	image/webp	110876	support-ticket/media/74/media_69969e60e80d42.67565666.webp	2026-02-19 05:23:45	74	support-ticket/media/74/thumb_media_69969e60e80d42.67565666.webp
200	media_69969e6408df88.81077772.webp	i (1).webp	image/webp	110698	support-ticket/media/74/media_69969e6408df88.81077772.webp	2026-02-19 05:23:48	74	support-ticket/media/74/thumb_media_69969e6408df88.81077772.webp
201	media_69969eea7cc331.69493769.webp	i (3).webp	image/webp	75086	support-ticket/media/74/media_69969eea7cc331.69493769.webp	2026-02-19 05:26:02	74	support-ticket/media/74/thumb_media_69969eea7cc331.69493769.webp
202	media_6996b7d6ce4be4.30158657.webp	1.webp	image/webp	105298	support-ticket/media/75/media_6996b7d6ce4be4.30158657.webp	2026-02-19 07:12:23	75	support-ticket/media/75/thumb_media_6996b7d6ce4be4.30158657.webp
203	media_6996b7d72fdb91.04198793.webp	i.webp	image/webp	100558	support-ticket/media/75/media_6996b7d72fdb91.04198793.webp	2026-02-19 07:12:23	75	support-ticket/media/75/thumb_media_6996b7d72fdb91.04198793.webp
204	media_6996b7d6c60d20.83333880.mp4	11079450429976.mp4	video/mp4	17004605	support-ticket/media/75/media_6996b7d6c60d20.83333880.mp4	2026-02-19 07:12:23	75	media/75/thumb_media_6996b7d6c60d20.83333880.mp4.jpg
205	media_6996b7d8be4b56.26551863.mp4	11203654584856.mp4	video/mp4	9199769	support-ticket/media/75/media_6996b7d8be4b56.26551863.mp4	2026-02-19 07:12:25	75	media/75/thumb_media_6996b7d8be4b56.26551863.mp4.jpg
206	media_6996b7da019eb4.19486257.mp4	11079835585048.mp4	video/mp4	6367404	support-ticket/media/75/media_6996b7da019eb4.19486257.mp4	2026-02-19 07:12:26	75	media/75/thumb_media_6996b7da019eb4.19486257.mp4.jpg
207	media_6996b8f4c3cf04.68905010.jpg	5240442512030241608.jpg	image/jpeg	104187	support-ticket/media/76/media_6996b8f4c3cf04.68905010.jpg	2026-02-19 07:17:08	76	support-ticket/media/76/thumb_media_6996b8f4c3cf04.68905010.jpg
208	media_69970fd8de26e5.22596120.jpg	37fa9279b048403be.jpg	image/jpeg	87660	support-ticket/media/76/media_69970fd8de26e5.22596120.jpg	2026-02-19 13:27:53	76	support-ticket/media/76/thumb_media_69970fd8de26e5.22596120.jpg
209	media_69970fd8dd5f71.02346227.jpg	3b64cfe742ddc7d31.jpg	image/jpeg	76347	support-ticket/media/76/media_69970fd8dd5f71.02346227.jpg	2026-02-19 13:27:53	76	support-ticket/media/76/thumb_media_69970fd8dd5f71.02346227.jpg
210	media_6997101bdf3e02.12546694.pdf	Письмо на ремонт станка.pdf	application/pdf	273104	support-ticket/media/77/media_6997101bdf3e02.12546694.pdf	2026-02-19 13:29:00	77	support-ticket/media/77/thumb_media_6997101bdf3e02.12546694.jpg
211	media_699716e89ea071.06220634.jpg	c2108ad64a67c81d7.jpg	image/jpeg	116773	support-ticket/media/76/media_699716e89ea071.06220634.jpg	2026-02-19 13:58:00	76	support-ticket/media/76/thumb_media_699716e89ea071.06220634.jpg
212	media_6998116f1e7a40.83595768.jpg	f380efde32121f4f4.jpg	image/jpeg	158151	support-ticket/media/79/media_6998116f1e7a40.83595768.jpg	2026-02-20 07:46:55	79	support-ticket/media/79/thumb_media_6998116f1e7a40.83595768.jpg
213	media_6998116f4ff850.91610694.jpg	0924669835a3e46e1.jpg	image/jpeg	176445	support-ticket/media/79/media_6998116f4ff850.91610694.jpg	2026-02-20 07:46:55	79	support-ticket/media/79/thumb_media_6998116f4ff850.91610694.jpg
214	media_6998116f688641.76973865.jpg	655d5fa093de424e4.jpg	image/jpeg	155028	support-ticket/media/79/media_6998116f688641.76973865.jpg	2026-02-20 07:46:55	79	support-ticket/media/79/thumb_media_6998116f688641.76973865.jpg
215	media_6998116f49a651.47223374.jpg	1f6f062639456e78c.jpg	image/jpeg	173257	support-ticket/media/79/media_6998116f49a651.47223374.jpg	2026-02-20 07:46:55	79	support-ticket/media/79/thumb_media_6998116f49a651.47223374.jpg
216	media_6998116fada3c7.79654704.jpg	9f42ff07bd09c99ee.jpg	image/jpeg	110690	support-ticket/media/79/media_6998116fada3c7.79654704.jpg	2026-02-20 07:46:56	79	support-ticket/media/79/thumb_media_6998116fada3c7.79654704.jpg
217	media_6998116fd32356.33628007.jpg	6d18ac0edc23e917c.jpg	image/jpeg	77555	support-ticket/media/79/media_6998116fd32356.33628007.jpg	2026-02-20 07:46:56	79	support-ticket/media/79/thumb_media_6998116fd32356.33628007.jpg
218	media_6998116feee644.47393595.jpg	54fe88b675f2a31e0.jpg	image/jpeg	89241	support-ticket/media/79/media_6998116feee644.47393595.jpg	2026-02-20 07:46:56	79	support-ticket/media/79/thumb_media_6998116feee644.47393595.jpg
219	media_6998117121ac54.77026167.mp4	0f8ee340781ce26d0.mp4	video/mp4	3454244	support-ticket/media/79/media_6998117121ac54.77026167.mp4	2026-02-20 07:46:57	79	media/79/thumb_media_6998117121ac54.77026167.mp4.jpg
220	media_699817b52a31a5.16939652.jpg	0cafe92f-0259-4822-ad0b-47fe57d1483d.jpg	image/jpeg	156260	support-ticket/media/79/media_699817b52a31a5.16939652.jpg	2026-02-20 08:13:41	79	support-ticket/media/79/thumb_media_699817b52a31a5.16939652.jpg
287	media_69d8e460c619d8.23579972.mp4	13229237865135 (1).mp4	video/mp4	7281489	support-ticket/media/108/media_69d8e460c619d8.23579972.mp4	2026-04-10 11:52:01	108	media/108/thumb_media_69d8e460c619d8.23579972.mp4.jpg
221	media_699817b52a8033.85684422.jpg	f277981d-675b-47b3-8cb4-84332f513d26.jpg	image/jpeg	154852	support-ticket/media/79/media_699817b52a8033.85684422.jpg	2026-02-20 08:13:41	79	support-ticket/media/79/thumb_media_699817b52a8033.85684422.jpg
222	media_699817b542e7c5.61360857.jpg	9c603007-fa48-459f-9bb8-9d9e6a8a1a35.jpg	image/jpeg	100689	support-ticket/media/79/media_699817b542e7c5.61360857.jpg	2026-02-20 08:13:41	79	support-ticket/media/79/thumb_media_699817b542e7c5.61360857.jpg
223	media_699817b53173f3.93101153.jpg	86f6853b-ef61-4f01-9983-def04fb58e11.jpg	image/jpeg	189631	support-ticket/media/79/media_699817b53173f3.93101153.jpg	2026-02-20 08:13:41	79	support-ticket/media/79/thumb_media_699817b53173f3.93101153.jpg
225	media_699817b578f757.58645470.jpg	9ea4cc6a-80f9-4d51-aa15-b731e1a918a4.jpg	image/jpeg	120800	support-ticket/media/79/media_699817b578f757.58645470.jpg	2026-02-20 08:13:41	79	support-ticket/media/79/thumb_media_699817b578f757.58645470.jpg
226	media_699817b57e36c9.84440581.jpg	a5608462-373b-40ed-b9c9-d1d558861bce.jpg	image/jpeg	125027	support-ticket/media/79/media_699817b57e36c9.84440581.jpg	2026-02-20 08:13:41	79	support-ticket/media/79/thumb_media_699817b57e36c9.84440581.jpg
227	media_699817b580a477.29510650.jpg	24eb064e-1b2f-4644-9ff6-fa76b5d79c92.jpg	image/jpeg	135527	support-ticket/media/79/media_699817b580a477.29510650.jpg	2026-02-20 08:13:41	79	support-ticket/media/79/thumb_media_699817b580a477.29510650.jpg
228	media_699817b5bf4d61.17804343.png	Без названия (1).png	image/png	392546	support-ticket/media/79/media_699817b5bf4d61.17804343.png	2026-02-20 08:13:41	79	support-ticket/media/79/thumb_media_699817b5bf4d61.17804343.png
224	media_699817b57634a3.58665369.jpg	ce26301b-ff0c-418a-b35f-3f111613c4fd.jpg	image/jpeg	168700	support-ticket/media/79/media_699817b57634a3.58665369.jpg	2026-02-20 08:13:41	79	support-ticket/media/79/thumb_media_699817b57634a3.58665369.jpg
229	media_69981aa4c86b18.73491029.jpg	e9165a91-e5a5-4a19-96bf-5fee8fa2afdf.jpg	image/jpeg	141176	support-ticket/media/79/media_69981aa4c86b18.73491029.jpg	2026-02-20 08:26:12	79	support-ticket/media/79/thumb_media_69981aa4c86b18.73491029.jpg
230	media_69986e3f5095f5.19223440.jpg	Image_20260220161335_1383_107.jpg	image/jpeg	438997	support-ticket/media/76/media_69986e3f5095f5.19223440.jpg	2026-02-20 14:22:55	76	support-ticket/media/76/thumb_media_69986e3f5095f5.19223440.jpg
231	media_699873c8f2f1a9.49711166.webp	i.webp	image/webp	177736	support-ticket/media/77/media_699873c8f2f1a9.49711166.webp	2026-02-20 14:46:33	77	support-ticket/media/77/thumb_media_699873c8f2f1a9.49711166.webp
232	media_699873ed178307.45262801.mp4	11537687120517.mp4	video/mp4	27903940	support-ticket/media/77/media_699873ed178307.45262801.mp4	2026-02-20 14:47:10	77	media/77/thumb_media_699873ed178307.45262801.mp4.jpg
233	media_699d50042584a1.96926000.mp4	11551428250363.mp4	video/mp4	5288955	support-ticket/media/80/media_699d50042584a1.96926000.mp4	2026-02-24 07:15:16	80	media/80/thumb_media_699d50042584a1.96926000.mp4.jpg
234	media_699d6c886f2850.70953402.webp	EvzeHmZ3.webp	image/webp	91398	support-ticket/media/81/media_699d6c886f2850.70953402.webp	2026-02-24 09:16:56	81	support-ticket/media/81/thumb_media_699d6c886f2850.70953402.webp
235	media_699dac5e158e21.95996804.jpg	photo_2026-02-24_16-48-18 (2).jpg	image/jpeg	99914	support-ticket/media/81/media_699dac5e158e21.95996804.jpg	2026-02-24 13:49:18	81	support-ticket/media/81/thumb_media_699dac5e158e21.95996804.jpg
236	media_69a834e46b5959.31712058.jpg	photo_5278753044784944669_y.jpg	image/jpeg	106681	support-ticket/media/86/media_69a834e46b5959.31712058.jpg	2026-03-04 13:34:28	86	support-ticket/media/86/thumb_media_69a834e46b5959.31712058.jpg
237	media_69a834e8577d49.51538438.mp4	document_5278753044324978358.mp4	video/mp4	2637373	support-ticket/media/86/media_69a834e8577d49.51538438.mp4	2026-03-04 13:34:35	86	media/86/thumb_media_69a834e8577d49.51538438.mp4.jpg
238	media_69a834ec4198c9.45642986.png	Без названия.png	image/png	679867	support-ticket/media/86/media_69a834ec4198c9.45642986.png	2026-03-04 13:34:36	86	support-ticket/media/86/thumb_media_69a834ec4198c9.45642986.png
239	media_69a834ec598e00.81421069.png	Без названия (1).png	image/png	994486	support-ticket/media/86/media_69a834ec598e00.81421069.png	2026-03-04 13:34:36	86	support-ticket/media/86/thumb_media_69a834ec598e00.81421069.png
240	media_69a834ec22a4d8.07099111.mp4	document_5278753044324978350.mp4	video/mp4	7429798	support-ticket/media/86/media_69a834ec22a4d8.07099111.mp4	2026-03-04 13:34:37	86	media/86/thumb_media_69a834ec22a4d8.07099111.mp4.jpg
241	media_69a834ef51b206.29637976.mp4	document_5278352259451753866.mp4	video/mp4	17620149	support-ticket/media/86/media_69a834ef51b206.29637976.mp4	2026-03-04 13:34:40	86	media/86/thumb_media_69a834ef51b206.29637976.mp4.jpg
242	media_69a924ceede6b2.17902419.jpg	817d47a0-6561-41f9-a7d7-249fca6d47fc.jpg	image/jpeg	116104	support-ticket/media/86/media_69a924ceede6b2.17902419.jpg	2026-03-05 06:38:07	86	support-ticket/media/86/thumb_media_69a924ceede6b2.17902419.jpg
243	media_69a97707308fa8.99211755.jpg	05.03.2026.jpg	image/jpeg	111270	support-ticket/media/87/media_69a97707308fa8.99211755.jpg	2026-03-05 12:28:55	87	support-ticket/media/87/thumb_media_69a97707308fa8.99211755.jpg
244	media_69a977834f6821.53795675.jpg	11.jpg	image/jpeg	207414	support-ticket/media/87/media_69a977834f6821.53795675.jpg	2026-03-05 12:30:59	87	support-ticket/media/87/thumb_media_69a977834f6821.53795675.jpg
245	media_69b918ee6ae7c3.46049457.png	52a33460f0d4704e54f58c635b9f40a8.png	image/png	1598517	support-ticket/media/91/media_69b918ee6ae7c3.46049457.png	2026-03-17 09:03:42	91	support-ticket/media/91/thumb_media_69b918ee6ae7c3.46049457.png
246	media_69b918f0e1f4a2.89856745.png	b0d45ba3f7ccbee68d852b6a4bdefeb9.png	image/png	831198	support-ticket/media/91/media_69b918f0e1f4a2.89856745.png	2026-03-17 09:03:45	91	support-ticket/media/91/thumb_media_69b918f0e1f4a2.89856745.png
247	media_69b918f262a055.56132212.png	003a5c68937fb7b6ea85b95c99cb618d.png	image/png	548159	support-ticket/media/91/media_69b918f262a055.56132212.png	2026-03-17 09:03:46	91	support-ticket/media/91/thumb_media_69b918f262a055.56132212.png
248	media_69b918f3c5db80.73385533.png	5bb1bda7680d3275f4c8808201fba1e8.png	image/png	658274	support-ticket/media/91/media_69b918f3c5db80.73385533.png	2026-03-17 09:03:48	91	support-ticket/media/91/thumb_media_69b918f3c5db80.73385533.png
249	media_69b918f569b028.01512800.png	fb79799a81d835afda061305e92e0bde.png	image/png	599660	support-ticket/media/91/media_69b918f569b028.01512800.png	2026-03-17 09:03:49	91	support-ticket/media/91/thumb_media_69b918f569b028.01512800.png
250	media_69b942b16eb061.78433639.png	61fa26050198287befecadb142f8e83c.png	image/png	644042	support-ticket/media/91/media_69b942b16eb061.78433639.png	2026-03-17 12:01:53	91	support-ticket/media/91/thumb_media_69b942b16eb061.78433639.png
251	media_69b942b3245997.20195804.png	814a445859f80d2b855818e95932dc80.png	image/png	814919	support-ticket/media/91/media_69b942b3245997.20195804.png	2026-03-17 12:01:55	91	support-ticket/media/91/thumb_media_69b942b3245997.20195804.png
252	media_69cb5af1c9ffa1.44884654.webp	i.webp	image/webp	189792	support-ticket/media/99/media_69cb5af1c9ffa1.44884654.webp	2026-03-31 05:26:10	99	support-ticket/media/99/thumb_media_69cb5af1c9ffa1.44884654.webp
253	media_69cb5b13a60732.10554566.webp	ef10ebba-6906-4860-a9bd-d48759804997.webp	image/webp	96586	support-ticket/media/99/media_69cb5b13a60732.10554566.webp	2026-03-31 05:26:43	99	support-ticket/media/99/thumb_media_69cb5b13a60732.10554566.webp
254	media_69cb5b13bfb020.99290907.webp	4f8dc380-1f4f-48a5-8848-dca205b377e1.webp	image/webp	111974	support-ticket/media/99/media_69cb5b13bfb020.99290907.webp	2026-03-31 05:26:44	99	support-ticket/media/99/thumb_media_69cb5b13bfb020.99290907.webp
255	media_69cb5b13b27dd7.91124022.webp	b1a7e889-b850-420d-9abd-f023608a17b8.webp	image/webp	210638	support-ticket/media/99/media_69cb5b13b27dd7.91124022.webp	2026-03-31 05:26:44	99	support-ticket/media/99/thumb_media_69cb5b13b27dd7.91124022.webp
256	media_69ccf759f07102.85539281.webp	0bb644f1-a452-43d6-a00e-95c13f477f7a.webp	image/webp	47670	support-ticket/media/96/media_69ccf759f07102.85539281.webp	2026-04-01 10:45:46	96	support-ticket/media/96/thumb_media_69ccf759f07102.85539281.webp
257	media_69ccf75a00d3d6.45769288.webp	d94d9c20-3c12-4978-98c0-dcec7962c8b5.webp	image/webp	102958	support-ticket/media/96/media_69ccf75a00d3d6.45769288.webp	2026-04-01 10:45:46	96	support-ticket/media/96/thumb_media_69ccf75a00d3d6.45769288.webp
258	media_69ccf75a0b8678.94557961.webp	c3d9f77b-7a03-4cb8-986c-6641a793f5e1.webp	image/webp	81216	support-ticket/media/96/media_69ccf75a0b8678.94557961.webp	2026-04-01 10:45:46	96	support-ticket/media/96/thumb_media_69ccf75a0b8678.94557961.webp
259	media_69ccf75a3cac73.98901941.webp	24d3e429-39e5-4b99-a266-3ad684b07050.webp	image/webp	51076	support-ticket/media/96/media_69ccf75a3cac73.98901941.webp	2026-04-01 10:45:46	96	support-ticket/media/96/thumb_media_69ccf75a3cac73.98901941.webp
261	media_69ccf75a4f0082.16544594.webp	85cb899b-07fc-4702-80d2-1aa3686bb32f.webp	image/webp	104444	support-ticket/media/96/media_69ccf75a4f0082.16544594.webp	2026-04-01 10:45:46	96	support-ticket/media/96/thumb_media_69ccf75a4f0082.16544594.webp
260	media_69ccf75a4fca27.21811056.webp	73212e54-bf5b-47e1-bdca-3599bec029c2.webp	image/webp	137082	support-ticket/media/96/media_69ccf75a4fca27.21811056.webp	2026-04-01 10:45:46	96	support-ticket/media/96/thumb_media_69ccf75a4fca27.21811056.webp
262	media_69ccf75a8fb2b8.39491371.webp	a3ac9bba-75ad-4b80-81cf-65639ac577c3.webp	image/webp	140946	support-ticket/media/96/media_69ccf75a8fb2b8.39491371.webp	2026-04-01 10:45:46	96	support-ticket/media/96/thumb_media_69ccf75a8fb2b8.39491371.webp
263	media_69ccf75a8f16b4.86529182.webp	0b035e27-77f0-47ae-9fe4-fc89b4d587b3.webp	image/webp	157760	support-ticket/media/96/media_69ccf75a8f16b4.86529182.webp	2026-04-01 10:45:46	96	support-ticket/media/96/thumb_media_69ccf75a8f16b4.86529182.webp
264	media_69ccf75acf0089.48266254.webp	aaafc98b-e3ff-4538-b2a6-082199ba3e5e.webp	image/webp	157760	support-ticket/media/96/media_69ccf75acf0089.48266254.webp	2026-04-01 10:45:46	96	support-ticket/media/96/thumb_media_69ccf75acf0089.48266254.webp
265	media_69ccf75b0349d9.63786775.webp	43cf912c-3682-4cef-b7df-6669df30778e.webp	image/webp	107568	support-ticket/media/96/media_69ccf75b0349d9.63786775.webp	2026-04-01 10:45:47	96	support-ticket/media/96/thumb_media_69ccf75b0349d9.63786775.webp
266	media_69ccf75c0a23f6.67067344.mp4	13451236084289.mp4	video/mp4	5681256	support-ticket/media/96/media_69ccf75c0a23f6.67067344.mp4	2026-04-01 10:45:49	96	media/96/thumb_media_69ccf75c0a23f6.67067344.mp4.jpg
267	media_69ccf75c24c367.59496202.mp4	13458251319873.mp4	video/mp4	3977404	support-ticket/media/96/media_69ccf75c24c367.59496202.mp4	2026-04-01 10:45:49	96	media/96/thumb_media_69ccf75c24c367.59496202.mp4.jpg
268	media_69ccf75d277835.03348487.mp4	13459027266113.mp4	video/mp4	13531874	support-ticket/media/96/media_69ccf75d277835.03348487.mp4	2026-04-01 10:45:49	96	media/96/thumb_media_69ccf75d277835.03348487.mp4.jpg
269	media_69cf9e29417eb2.21029410.mp4	13421593889531.mp4	video/mp4	6832409	support-ticket/media/101/media_69cf9e29417eb2.21029410.mp4	2026-04-03 11:02:01	101	media/101/thumb_media_69cf9e29417eb2.21029410.mp4.jpg
270	media_69cfa3c301d451.15277741.webp	65071902-8761-4345-8005-1aae514c223d.webp	image/webp	79970	support-ticket/media/101/media_69cfa3c301d451.15277741.webp	2026-04-03 11:25:55	101	support-ticket/media/101/thumb_media_69cfa3c301d451.15277741.webp
271	media_69cfa5d99e7184.03659479.webp	f6c66ea3-5496-48dc-8656-cefda8b11f8a.webp	image/webp	88064	support-ticket/media/101/media_69cfa5d99e7184.03659479.webp	2026-04-03 11:34:49	101	support-ticket/media/101/thumb_media_69cfa5d99e7184.03659479.webp
272	media_69d3442c487d60.75482368.png	IMG_20260406_081049.png	image/png	826146	support-ticket/media/102/media_69d3442c487d60.75482368.png	2026-04-06 05:27:08	102	support-ticket/media/102/thumb_media_69d3442c487d60.75482368.png
273	media_69d3442c7bb607.63632302.png	IMG_20260406_081055.png	image/png	833588	support-ticket/media/102/media_69d3442c7bb607.63632302.png	2026-04-06 05:27:08	102	support-ticket/media/102/thumb_media_69d3442c7bb607.63632302.png
274	media_69d4c1747f8529.27212296.mp4	13055420599024.mp4	video/mp4	7141291	support-ticket/media/102/media_69d4c1747f8529.27212296.mp4	2026-04-07 08:33:57	102	media/102/thumb_media_69d4c1747f8529.27212296.mp4.jpg
275	media_69d4c2263818f9.83845800.png	4444444444444444.png	image/png	842236	support-ticket/media/102/media_69d4c2263818f9.83845800.png	2026-04-07 08:36:54	102	support-ticket/media/102/thumb_media_69d4c2263818f9.83845800.png
276	media_69d4f5fd58d578.77633064.mp4	5680373a350fad763.mp4	video/mp4	9956959	support-ticket/media/105/media_69d4f5fd58d578.77633064.mp4	2026-04-07 12:18:06	105	media/105/thumb_media_69d4f5fd58d578.77633064.mp4.jpg
277	media_69d7a13013d554.73319350.webp	e828d0dd-c5c5-4282-ba92-58ccc41ef933.webp	image/webp	57388	support-ticket/media/108/media_69d7a13013d554.73319350.webp	2026-04-09 12:53:04	108	support-ticket/media/108/thumb_media_69d7a13013d554.73319350.webp
278	media_69d7a13038f482.89997175.webp	e70e50a9-e168-4d42-b0c0-94edcb905c60.webp	image/webp	110752	support-ticket/media/108/media_69d7a13038f482.89997175.webp	2026-04-09 12:53:04	108	support-ticket/media/108/thumb_media_69d7a13038f482.89997175.webp
279	media_69d7a13064f2e6.74990521.webp	e47e2183-10aa-40d9-a03a-2b768af019a7.webp	image/webp	69280	support-ticket/media/108/media_69d7a13064f2e6.74990521.webp	2026-04-09 12:53:04	108	support-ticket/media/108/thumb_media_69d7a13064f2e6.74990521.webp
280	media_69d8e45662fd67.69752959.webp	b2ebe8e5-6326-419c-ad94-c04d81a4cfb7.webp	image/webp	32958	support-ticket/media/108/media_69d8e45662fd67.69752959.webp	2026-04-10 11:51:50	108	support-ticket/media/108/thumb_media_69d8e45662fd67.69752959.webp
281	media_69d8e4566c2a42.85058472.webp	780e4ba3-78d3-445b-8a15-70aa854d2da0.webp	image/webp	32958	support-ticket/media/108/media_69d8e4566c2a42.85058472.webp	2026-04-10 11:51:50	108	support-ticket/media/108/thumb_media_69d8e4566c2a42.85058472.webp
282	media_69d8e45677d7a3.50402677.webp	96dd8aaa-5a9e-4f8c-816f-c7531e4c2b89.webp	image/webp	35814	support-ticket/media/108/media_69d8e45677d7a3.50402677.webp	2026-04-10 11:51:50	108	support-ticket/media/108/thumb_media_69d8e45677d7a3.50402677.webp
283	media_69d8e456a47270.10768803.webp	05d44e47-a294-4ed7-8d65-c494dc00b57a.webp	image/webp	34314	support-ticket/media/108/media_69d8e456a47270.10768803.webp	2026-04-10 11:51:50	108	support-ticket/media/108/thumb_media_69d8e456a47270.10768803.webp
284	media_69d8e457058334.54975300.webp	bdfc5c14-737b-4843-af3a-16160dec9ad7.webp	image/webp	21652	support-ticket/media/108/media_69d8e457058334.54975300.webp	2026-04-10 11:51:51	108	support-ticket/media/108/thumb_media_69d8e457058334.54975300.webp
285	media_69d8e4571c0c48.93693037.webp	bd4dbdcb-7f25-4279-bfbf-9b3a6f74b4c8.webp	image/webp	21652	support-ticket/media/108/media_69d8e4571c0c48.93693037.webp	2026-04-10 11:51:51	108	support-ticket/media/108/thumb_media_69d8e4571c0c48.93693037.webp
286	media_69d8e460d5cb61.00665548.mp4	13229237865135.mp4	video/mp4	7281489	support-ticket/media/108/media_69d8e460d5cb61.00665548.mp4	2026-04-10 11:52:01	108	media/108/thumb_media_69d8e460d5cb61.00665548.mp4.jpg
288	media_69d8e4652104a3.17863372.mp4	13223116999343.mp4	video/mp4	19544769	support-ticket/media/108/media_69d8e4652104a3.17863372.mp4	2026-04-10 11:52:05	108	media/108/thumb_media_69d8e4652104a3.17863372.mp4.jpg
289	media_69dc86a0ad4b58.18361539.PNG	IMG_8410.PNG	image/png	636679	support-ticket/media/109/media_69dc86a0ad4b58.18361539.PNG	2026-04-13 06:01:04	109	support-ticket/media/109/thumb_media_69dc86a0ad4b58.18361539.PNG
290	media_69dc9144d153b8.90034368.webp	87032b79-1557-4fd0-a474-6c18afb27e97.webp	image/webp	130924	support-ticket/media/109/media_69dc9144d153b8.90034368.webp	2026-04-13 06:46:29	109	support-ticket/media/109/thumb_media_69dc9144d153b8.90034368.webp
291	media_69dc9144eeb575.06798813.webp	3c04201e-401b-4bd0-9911-3dad0802e936.webp	image/webp	149214	support-ticket/media/109/media_69dc9144eeb575.06798813.webp	2026-04-13 06:46:29	109	support-ticket/media/109/thumb_media_69dc9144eeb575.06798813.webp
292	media_69dc9144f315b3.21986768.webp	89d654ff-41ca-4e9f-9a61-1e5f4e676028.webp	image/webp	106284	support-ticket/media/109/media_69dc9144f315b3.21986768.webp	2026-04-13 06:46:29	109	support-ticket/media/109/thumb_media_69dc9144f315b3.21986768.webp
293	media_69dc9145097706.54238927.webp	a7e5161f-4593-4f9d-bc4c-eab30a457b5a.webp	image/webp	94486	support-ticket/media/109/media_69dc9145097706.54238927.webp	2026-04-13 06:46:29	109	support-ticket/media/109/thumb_media_69dc9145097706.54238927.webp
294	media_69dc914526b985.81945125.webp	a50f72ca-108f-4af6-9b9e-9c7f95ff397e.webp	image/webp	98764	support-ticket/media/109/media_69dc914526b985.81945125.webp	2026-04-13 06:46:29	109	support-ticket/media/109/thumb_media_69dc914526b985.81945125.webp
295	media_69dc91454cf751.22388565.webp	5255ad85-fc1d-4e1d-85e2-8edabc820cc3.webp	image/webp	62814	support-ticket/media/109/media_69dc91454cf751.22388565.webp	2026-04-13 06:46:29	109	support-ticket/media/109/thumb_media_69dc91454cf751.22388565.webp
296	media_69dc9151d085e6.25384055.mp4	13847580445227.mp4	video/mp4	26133790	support-ticket/media/109/media_69dc9151d085e6.25384055.mp4	2026-04-13 06:46:42	109	media/109/thumb_media_69dc9151d085e6.25384055.mp4.jpg
297	media_69dc915357efe3.05225464.mp4	13847590144555.mp4	video/mp4	33594675	support-ticket/media/109/media_69dc915357efe3.05225464.mp4	2026-04-13 06:46:44	109	media/109/thumb_media_69dc915357efe3.05225464.mp4.jpg
298	media_69dcd8b06e76f5.97568914.mp4	13290440886989(2).mp4	video/mp4	1595152	support-ticket/media/105/media_69dcd8b06e76f5.97568914.mp4	2026-04-13 11:51:12	105	media/105/thumb_media_69dcd8b06e76f5.97568914.mp4.jpg
299	media_69dcd8b168b9d7.84847697.mp4	13313827932877.mp4	video/mp4	6670182	support-ticket/media/105/media_69dcd8b168b9d7.84847697.mp4	2026-04-13 11:51:14	105	media/105/thumb_media_69dcd8b168b9d7.84847697.mp4.jpg
300	media_69ddf0e32880a6.62872775.png	Image_20260414104325_19_143.png	image/png	109235	support-ticket/media/110/media_69ddf0e32880a6.62872775.png	2026-04-14 07:46:43	110	support-ticket/media/110/thumb_media_69ddf0e32880a6.62872775.png
301	media_69ddf0e3764507.97814377.png	Image_20260414104236_17_143.png	image/png	671439	support-ticket/media/110/media_69ddf0e3764507.97814377.png	2026-04-14 07:46:43	110	support-ticket/media/110/thumb_media_69ddf0e3764507.97814377.png
302	media_69ddf0e3d60130.80138867.png	Image_20260414104249_18_143.png	image/png	2164793	support-ticket/media/110/media_69ddf0e3d60130.80138867.png	2026-04-14 07:46:44	110	support-ticket/media/110/thumb_media_69ddf0e3d60130.80138867.png
303	media_69ddf0e3279c32.26124676.mp4	13128975977169.mp4	video/mp4	4600131	support-ticket/media/110/media_69ddf0e3279c32.26124676.mp4	2026-04-14 07:46:45	110	media/110/thumb_media_69ddf0e3279c32.26124676.mp4.jpg
304	media_69ddf0e34f3fe8.50796274.mp4	13143615474385.mp4	video/mp4	2903618	support-ticket/media/110/media_69ddf0e34f3fe8.50796274.mp4	2026-04-14 07:46:45	110	media/110/thumb_media_69ddf0e34f3fe8.50796274.mp4.jpg
305	media_69ddf0e3acb526.14459660.mp4	13153083329233.mp4	video/mp4	8418789	support-ticket/media/110/media_69ddf0e3acb526.14459660.mp4	2026-04-14 07:46:45	110	media/110/thumb_media_69ddf0e3acb526.14459660.mp4.jpg
306	media_69de388fc4fa94.44846094.mp4	14482459200102.mp4	video/mp4	30208870	support-ticket/media/111/media_69de388fc4fa94.44846094.mp4	2026-04-14 12:52:33	111	media/111/thumb_media_69de388fc4fa94.44846094.mp4.jpg
307	media_69de38913364f1.70180194.webp	8777ba87-78f8-497e-bf96-7f283b3e1748.webp	image/webp	220428	support-ticket/media/111/media_69de38913364f1.70180194.webp	2026-04-14 12:52:33	111	support-ticket/media/111/thumb_media_69de38913364f1.70180194.webp
308	media_69de38a8a55c38.77163812.mp4	13937480960555.mp4	video/mp4	9865534	support-ticket/media/109/media_69de38a8a55c38.77163812.mp4	2026-04-14 12:52:57	109	media/109/thumb_media_69de38a8a55c38.77163812.mp4.jpg
309	media_69de38b0ba6499.03071069.mp4	13936750561835.mp4	video/mp4	13691498	support-ticket/media/109/media_69de38b0ba6499.03071069.mp4	2026-04-14 12:53:05	109	media/109/thumb_media_69de38b0ba6499.03071069.mp4.jpg
310	media_69de38b50a9202.39355943.mp4	13935668365867.mp4	video/mp4	17935577	support-ticket/media/109/media_69de38b50a9202.39355943.mp4	2026-04-14 12:53:10	109	media/109/thumb_media_69de38b50a9202.39355943.mp4.jpg
311	media_69de38b57255e4.73125613.mp4	13934243613227.mp4	video/mp4	9099455	support-ticket/media/109/media_69de38b57255e4.73125613.mp4	2026-04-14 12:53:10	109	media/109/thumb_media_69de38b57255e4.73125613.mp4.jpg
312	media_69de38b5b9fdb9.45751199.mp4	13935660239403.mp4	video/mp4	19618476	support-ticket/media/109/media_69de38b5b9fdb9.45751199.mp4	2026-04-14 12:53:10	109	media/109/thumb_media_69de38b5b9fdb9.45751199.mp4.jpg
313	media_69de41f99136b1.22226408.mp4	13947031194155.mp4	video/mp4	2633149	support-ticket/media/109/media_69de41f99136b1.22226408.mp4	2026-04-14 13:32:42	109	media/109/thumb_media_69de41f99136b1.22226408.mp4.jpg
314	media_69de4204303409.41914435.mp4	13947030538795.mp4	video/mp4	8624820	support-ticket/media/109/media_69de4204303409.41914435.mp4	2026-04-14 13:32:52	109	media/109/thumb_media_69de4204303409.41914435.mp4.jpg
315	media_69de4208dca9a9.44061771.mp4	13947028245035.mp4	video/mp4	19606266	support-ticket/media/109/media_69de4208dca9a9.44061771.mp4	2026-04-14 13:32:58	109	media/109/thumb_media_69de4208dca9a9.44061771.mp4.jpg
316	media_69de4208dd0533.98116269.mp4	13946692831787.mp4	video/mp4	20033801	support-ticket/media/109/media_69de4208dd0533.98116269.mp4	2026-04-14 13:32:58	109	media/109/thumb_media_69de4208dd0533.98116269.mp4.jpg
317	media_69de420c9c7e56.67116254.mp4	13947657128491.mp4	video/mp4	38366102	support-ticket/media/109/media_69de420c9c7e56.67116254.mp4	2026-04-14 13:33:01	109	media/109/thumb_media_69de420c9c7e56.67116254.mp4.jpg
318	media_69e07d28315739.48186605.jpg	22ebbf4e-939b-47c9-8ef1-56d04d2ba8ba.jpg	image/jpeg	67872	support-ticket/media/111/media_69e07d28315739.48186605.jpg	2026-04-16 06:09:44	111	support-ticket/media/111/thumb_media_69e07d28315739.48186605.jpg
319	media_69e07d28388935.24502262.jpg	2c07d7e5-c8b2-4b14-86a5-47ccef253e2e.jpg	image/jpeg	78084	support-ticket/media/111/media_69e07d28388935.24502262.jpg	2026-04-16 06:09:44	111	support-ticket/media/111/thumb_media_69e07d28388935.24502262.jpg
320	media_69e07d28510c13.87742429.jpg	0fa3b6fa-a8c4-4e60-9470-d852c41da646.jpg	image/jpeg	449164	support-ticket/media/111/media_69e07d28510c13.87742429.jpg	2026-04-16 06:09:44	111	support-ticket/media/111/thumb_media_69e07d28510c13.87742429.jpg
321	media_69e07d285c9104.86074221.jpg	07ec7287-5548-4dba-9f31-cf5170b8cc72.jpg	image/jpeg	294459	support-ticket/media/111/media_69e07d285c9104.86074221.jpg	2026-04-16 06:09:44	111	support-ticket/media/111/thumb_media_69e07d285c9104.86074221.jpg
322	media_69e07d28619527.35940630.jpg	31a50f43-c38b-4b99-9fa3-5e9c384deab1.jpg	image/jpeg	446053	support-ticket/media/111/media_69e07d28619527.35940630.jpg	2026-04-16 06:09:44	111	support-ticket/media/111/thumb_media_69e07d28619527.35940630.jpg
323	media_69e07d5768ebc2.25127045.pdf	MetalTec HBM 125_2500C РЭ ред 2022 08.pdf	application/pdf	1999762	support-ticket/media/111/media_69e07d5768ebc2.25127045.pdf	2026-04-16 06:10:34	111	support-ticket/media/111/thumb_media_69e07d5768ebc2.25127045.jpg
324	media_69e092247d41c6.01005721.jpg	7977bb04-71fd-45e7-a056-0b6b37ace152.jpg	image/jpeg	167011	support-ticket/media/112/media_69e092247d41c6.01005721.jpg	2026-04-16 07:39:16	112	support-ticket/media/112/thumb_media_69e092247d41c6.01005721.jpg
325	media_69e09224a23514.73098965.jpg	2ba8ff80-e9fc-48f5-b464-12ad13ad03d7.jpg	image/jpeg	141290	support-ticket/media/112/media_69e09224a23514.73098965.jpg	2026-04-16 07:39:16	112	support-ticket/media/112/thumb_media_69e09224a23514.73098965.jpg
326	media_69e09224c77b35.66430034.jpg	76ec0bbf-9558-4dd0-ad83-addc231943b7.jpg	image/jpeg	387326	support-ticket/media/112/media_69e09224c77b35.66430034.jpg	2026-04-16 07:39:16	112	support-ticket/media/112/thumb_media_69e09224c77b35.66430034.jpg
327	media_69e09224f14be5.87918963.jpg	88825931-8c56-48ea-8582-5add0ae29b69.jpg	image/jpeg	297193	support-ticket/media/112/media_69e09224f14be5.87918963.jpg	2026-04-16 07:39:17	112	support-ticket/media/112/thumb_media_69e09224f14be5.87918963.jpg
328	media_69e0922527af01.18702751.jpg	0093e959-f008-4077-89b7-3355eb6decbc.jpg	image/jpeg	388922	support-ticket/media/112/media_69e0922527af01.18702751.jpg	2026-04-16 07:39:17	112	support-ticket/media/112/thumb_media_69e0922527af01.18702751.jpg
329	media_69e0b0bab86e72.58366705.jpg	e99eb39c-8372-461b-9aee-1b407666f9e1.jpg	image/jpeg	188263	support-ticket/media/112/media_69e0b0bab86e72.58366705.jpg	2026-04-16 09:49:46	112	support-ticket/media/112/thumb_media_69e0b0bab86e72.58366705.jpg
330	media_69e71e2d85fdf2.34401226.jpg	IMG_20260421_100328.jpg	image/jpeg	273315	support-ticket/media/113/media_69e71e2d85fdf2.34401226.jpg	2026-04-21 06:50:21	113	support-ticket/media/113/thumb_media_69e71e2d85fdf2.34401226.jpg
331	media_69e71e2db48c93.74250286.jpg	IMG_20260421_100753.jpg	image/jpeg	254860	support-ticket/media/113/media_69e71e2db48c93.74250286.jpg	2026-04-21 06:50:21	113	support-ticket/media/113/thumb_media_69e71e2db48c93.74250286.jpg
332	media_69e71e2e39db64.35589362.jpg	IMG_20260421_100808.jpg	image/jpeg	184488	support-ticket/media/113/media_69e71e2e39db64.35589362.jpg	2026-04-21 06:50:22	113	support-ticket/media/113/thumb_media_69e71e2e39db64.35589362.jpg
333	media_69e71e2e39e104.87424079.jpg	IMG_20260421_100347.jpg	image/jpeg	265824	support-ticket/media/113/media_69e71e2e39e104.87424079.jpg	2026-04-21 06:50:22	113	support-ticket/media/113/thumb_media_69e71e2e39e104.87424079.jpg
334	media_69e71e2e779017.40226127.jpg	IMG_20260421_100802.jpg	image/jpeg	191068	support-ticket/media/113/media_69e71e2e779017.40226127.jpg	2026-04-21 06:50:22	113	support-ticket/media/113/thumb_media_69e71e2e779017.40226127.jpg
335	media_69e71e2e87e2d7.96468026.jpg	IMG_20260421_100815.jpg	image/jpeg	194423	support-ticket/media/113/media_69e71e2e87e2d7.96468026.jpg	2026-04-21 06:50:22	113	support-ticket/media/113/thumb_media_69e71e2e87e2d7.96468026.jpg
336	media_69e71e2e7c4e13.96026717.jpg	IMG_20260421_100337.jpg	image/jpeg	236984	support-ticket/media/113/media_69e71e2e7c4e13.96026717.jpg	2026-04-21 06:50:22	113	support-ticket/media/113/thumb_media_69e71e2e7c4e13.96026717.jpg
337	media_69e740b8a3eda9.25473535.jpg	пароль лазер.jpg	image/jpeg	1969647	support-ticket/media/113/media_69e740b8a3eda9.25473535.jpg	2026-04-21 09:17:44	113	support-ticket/media/113/thumb_media_69e740b8a3eda9.25473535.jpg
\.


--
-- Data for Name: user; Type: TABLE DATA; Schema: public; Owner: -
--

COPY "public"."user" ("id", "email", "first_name", "last_name") FROM stdin;
1	manager@stankoff.ru	Manager	Support
2	service-account-stankoff-api	Service	Account
3	sergey.mavrin@stankoff.ru	Sergey	Mavrin
4	viktor.karasev@stankoff.ru	Viktor	Karasev
5	aleksei.matveev@stankoff.ru	Алексей	Матвеев
6	anastasia.karyukhina@stankoff.ru	Анастасия	Карюхина
7	oksana.shigabutdinova@stankoff.ru	Оксана	Шигабутдинова
8	admin@stankoff.ru	Admin	Stankoff
9	ruslan.stankoff@gmail.com	Руслан Альфирович	Зиннуров
\.


--
-- Name: support_ticket_comment_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('"public"."support_ticket_comment_id_seq"', 411, true);


--
-- Name: support_ticket_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('"public"."support_ticket_id_seq"', 116, true);


--
-- Name: support_ticket_media_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('"public"."support_ticket_media_id_seq"', 337, true);


--
-- Name: user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('"public"."user_id_seq"', 9, true);


--
-- Name: doctrine_migration_versions doctrine_migration_versions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "public"."doctrine_migration_versions"
    ADD CONSTRAINT "doctrine_migration_versions_pkey" PRIMARY KEY ("version");


--
-- Name: support_ticket_comment support_ticket_comment_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "public"."support_ticket_comment"
    ADD CONSTRAINT "support_ticket_comment_pkey" PRIMARY KEY ("id");


--
-- Name: support_ticket_media support_ticket_media_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "public"."support_ticket_media"
    ADD CONSTRAINT "support_ticket_media_pkey" PRIMARY KEY ("id");


--
-- Name: support_ticket support_ticket_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "public"."support_ticket"
    ADD CONSTRAINT "support_ticket_pkey" PRIMARY KEY ("id");


--
-- Name: user user_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "public"."user"
    ADD CONSTRAINT "user_pkey" PRIMARY KEY ("id");


--
-- Name: idx_1f5a4d53a76ed395; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX "idx_1f5a4d53a76ed395" ON "public"."support_ticket" USING "btree" ("user_id");


--
-- Name: idx_51ec784fa76ed395; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX "idx_51ec784fa76ed395" ON "public"."support_ticket_comment" USING "btree" ("user_id");


--
-- Name: idx_51ec784fc6d2dc64; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX "idx_51ec784fc6d2dc64" ON "public"."support_ticket_comment" USING "btree" ("support_ticket_id");


--
-- Name: idx_79a7a0bac6d2dc64; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX "idx_79a7a0bac6d2dc64" ON "public"."support_ticket_media" USING "btree" ("support_ticket_id");


--
-- Name: uniq_8d93d649e7927c74; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX "uniq_8d93d649e7927c74" ON "public"."user" USING "btree" ("email");


--
-- Name: support_ticket fk_1f5a4d53a76ed395; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "public"."support_ticket"
    ADD CONSTRAINT "fk_1f5a4d53a76ed395" FOREIGN KEY ("user_id") REFERENCES "public"."user"("id");


--
-- Name: support_ticket_comment fk_51ec784fa76ed395; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "public"."support_ticket_comment"
    ADD CONSTRAINT "fk_51ec784fa76ed395" FOREIGN KEY ("user_id") REFERENCES "public"."user"("id");


--
-- Name: support_ticket_comment fk_51ec784fc6d2dc64; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "public"."support_ticket_comment"
    ADD CONSTRAINT "fk_51ec784fc6d2dc64" FOREIGN KEY ("support_ticket_id") REFERENCES "public"."support_ticket"("id");


--
-- Name: support_ticket_media fk_79a7a0bac6d2dc64; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "public"."support_ticket_media"
    ADD CONSTRAINT "fk_79a7a0bac6d2dc64" FOREIGN KEY ("support_ticket_id") REFERENCES "public"."support_ticket"("id");


--
-- PostgreSQL database dump complete
--

\unrestrict e6MWZGhFifdRO9Oq7OsRkADs7yUofWU6aHBYbJ2AvUM5hSAJ1aHGiOG5aXbzm9j

