--
-- PostgreSQL database dump
--

\restrict ykgAPms7JuencQnxoURo8Q8xwnyCBWRbFg58uHqNxSZquI07Re1YDLdSZKugCKu

-- Dumped from database version 14.19 (Ubuntu 14.19-0ubuntu0.22.04.1)
-- Dumped by pg_dump version 14.19 (Ubuntu 14.19-0ubuntu0.22.04.1)

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

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: api_key_usage; Type: TABLE; Schema: public; Owner: jackson
--

CREATE TABLE public.api_key_usage (
    api_key character varying(64) NOT NULL,
    time_window timestamp with time zone NOT NULL,
    count integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.api_key_usage OWNER TO jackson;

--
-- Name: api_keys; Type: TABLE; Schema: public; Owner: jackson
--

CREATE TABLE public.api_keys (
    id integer NOT NULL,
    api_key character varying(64) NOT NULL,
    description text DEFAULT ''::text,
    active boolean DEFAULT true NOT NULL,
    admin boolean DEFAULT false NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    rate_limit integer DEFAULT 60 NOT NULL
);


ALTER TABLE public.api_keys OWNER TO jackson;

--
-- Name: api_keys_id_seq; Type: SEQUENCE; Schema: public; Owner: jackson
--

CREATE SEQUENCE public.api_keys_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.api_keys_id_seq OWNER TO jackson;

--
-- Name: api_keys_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: jackson
--

ALTER SEQUENCE public.api_keys_id_seq OWNED BY public.api_keys.id;


--
-- Name: login_info; Type: TABLE; Schema: public; Owner: jackson
--

CREATE TABLE public.login_info (
    username character varying(14),
    password character varying(60),
    token character varying(32)
);


ALTER TABLE public.login_info OWNER TO jackson;

--
-- Name: messages; Type: TABLE; Schema: public; Owner: jackson
--

CREATE TABLE public.messages (
    id integer NOT NULL,
    sender character varying(14),
    recipient character varying(14),
    message text,
    method character varying(16),
    "timestamp" timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.messages OWNER TO jackson;

--
-- Name: messages_id_seq; Type: SEQUENCE; Schema: public; Owner: jackson
--

CREATE SEQUENCE public.messages_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.messages_id_seq OWNER TO jackson;

--
-- Name: messages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: jackson
--

ALTER SEQUENCE public.messages_id_seq OWNED BY public.messages.id;


--
-- Name: public_keys; Type: TABLE; Schema: public; Owner: jackson
--

CREATE TABLE public.public_keys (
    username character varying(14),
    public_key text,
    method character varying(16)
);


ALTER TABLE public.public_keys OWNER TO jackson;

--
-- Name: api_keys id; Type: DEFAULT; Schema: public; Owner: jackson
--

ALTER TABLE ONLY public.api_keys ALTER COLUMN id SET DEFAULT nextval('public.api_keys_id_seq'::regclass);


--
-- Name: messages id; Type: DEFAULT; Schema: public; Owner: jackson
--

ALTER TABLE ONLY public.messages ALTER COLUMN id SET DEFAULT nextval('public.messages_id_seq'::regclass);


--
-- Name: api_key_usage api_key_usage_pkey; Type: CONSTRAINT; Schema: public; Owner: jackson
--

ALTER TABLE ONLY public.api_key_usage
    ADD CONSTRAINT api_key_usage_pkey PRIMARY KEY (api_key, time_window);


--
-- Name: api_keys api_keys_api_key_key; Type: CONSTRAINT; Schema: public; Owner: jackson
--

ALTER TABLE ONLY public.api_keys
    ADD CONSTRAINT api_keys_api_key_key UNIQUE (api_key);


--
-- Name: api_keys api_keys_pkey; Type: CONSTRAINT; Schema: public; Owner: jackson
--

ALTER TABLE ONLY public.api_keys
    ADD CONSTRAINT api_keys_pkey PRIMARY KEY (id);


--
-- Name: messages messages_pkey; Type: CONSTRAINT; Schema: public; Owner: jackson
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_pkey PRIMARY KEY (id);


--
-- PostgreSQL database dump complete
--

\unrestrict ykgAPms7JuencQnxoURo8Q8xwnyCBWRbFg58uHqNxSZquI07Re1YDLdSZKugCKu

