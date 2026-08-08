-- =====================================================
-- TourManager
-- Initial database schema
-- =====================================================


CREATE DATABASE IF NOT EXISTS tourmanager
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;


USE tourmanager;



-- =========================
-- Persons
-- =========================

CREATE TABLE persons (

    id INT AUTO_INCREMENT PRIMARY KEY,

    firstname VARCHAR(100) NOT NULL,

    lastname VARCHAR(100) NOT NULL,

    role ENUM(
        'DJ',
        'TOUR_MANAGER',
        'GUEST',
        'OTHER'
    ) NOT NULL DEFAULT 'OTHER',

    phone VARCHAR(50),

    email VARCHAR(150),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);



-- =========================
-- Tours
-- =========================

CREATE TABLE tours (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(200) NOT NULL,

    destination VARCHAR(200),

    start_date DATE,

    end_date DATE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);



-- =========================
-- Tour participants
-- =========================

CREATE TABLE tour_persons (

    id INT AUTO_INCREMENT PRIMARY KEY,

    tour_id INT NOT NULL,

    person_id INT NOT NULL,


    CONSTRAINT fk_tp_tour

    FOREIGN KEY(tour_id)

    REFERENCES tours(id)

    ON DELETE CASCADE,


    CONSTRAINT fk_tp_person

    FOREIGN KEY(person_id)

    REFERENCES persons(id)

    ON DELETE CASCADE,


    UNIQUE(tour_id, person_id)

);



-- =========================
-- Daily planning
-- =========================

CREATE TABLE tour_days (

    id INT AUTO_INCREMENT PRIMARY KEY,


    tour_id INT NOT NULL,


    day_number INT NOT NULL,


    date DATE NOT NULL,


    country VARCHAR(100),

    city VARCHAR(150),

    venue VARCHAR(200),


    notes TEXT,


    FOREIGN KEY(tour_id)

    REFERENCES tours(id)

    ON DELETE CASCADE

);



-- =========================
-- Hotels
-- =========================

CREATE TABLE accommodations (

    id INT AUTO_INCREMENT PRIMARY KEY,


    tour_day_id INT NOT NULL,


    name VARCHAR(200),


    address TEXT,


    phone VARCHAR(50),


    checkin TIME,


    checkout TIME,


    FOREIGN KEY(tour_day_id)

    REFERENCES tour_days(id)

    ON DELETE CASCADE

);



-- =========================
-- Transport
-- =========================

CREATE TABLE transports (

    id INT AUTO_INCREMENT PRIMARY KEY,


    tour_day_id INT NOT NULL,


    type ENUM(

        'PLANE',

        'TRAIN',

        'CAR',

        'DRIVER',

        'OTHER'

    ) NOT NULL,


    departure VARCHAR(200),


    arrival VARCHAR(200),


    departure_time DATETIME,


    arrival_time DATETIME,


    provider VARCHAR(200),


    reference_number VARCHAR(100),


    notes TEXT,


    FOREIGN KEY(tour_day_id)

    REFERENCES tour_days(id)

    ON DELETE CASCADE

);



-- =========================
-- Agenda
-- =========================

CREATE TABLE agenda_items (

    id INT AUTO_INCREMENT PRIMARY KEY,


    tour_day_id INT NOT NULL,


    time TIME,


    title VARCHAR(200) NOT NULL,


    description TEXT,


    FOREIGN KEY(tour_day_id)

    REFERENCES tour_days(id)

    ON DELETE CASCADE

);
