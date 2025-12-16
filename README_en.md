[中文版](README.md)

# Restaurant Management System

## Overview

A lightweight PHP + MySQL restaurant system that covers user ordering with points, table reservations, points redemption and history; and staff-side order processing, reservation management, inventory replenishment, and profile (avatar) maintenance. It uses native HTML/CSS/JS and `mysqli`, no frameworks, suitable for coursework and demos.

## Features

- User
- Register / Login / Logout
- Shopping cart ordering with notes, points accrue at 1 point per 1 RMB
- Membership level display: Bronze / Silver / Gold
- Table reservation with capacity checks and occupancy validation
- Points redemption for gifts, with exchange records
- Order and reservation history
- Staff
- Order management: complete / cancel; completion reduces related inventory
- Reservation management: cancel (delete records)
- Inventory management: replenish quantities and auto status update (Sufficient / Need Replenishment)
- Profile: avatar upload / delete (max 2MB, JPEG/PNG/GIF)
- Data initialization
- On first run, inserts membership levels, products, inventory, tables, gifts, and a staff account (`staff/123456`)

## Tech Stack

- Backend: PHP 7+, `mysqli`
- Frontend: HTML, CSS, vanilla JS
- Database: MySQL (`restaurant.sql` creates schema, `utf8mb4`)

## Quick Start

Put the `pro` folder under `XAMPP/htdocs`, start Apache/MySQL, execute `restaurant.sql`, configure `config.php` (defaults below), then open `http://localhost/pro/login.php`.

- Defaults in `config.php`:
- `DB_HOST=localhost`
- `DB_USER=root`
- `DB_PASS=` (empty)
- `DB_NAME=restaurant`
- `restaurant.sql` includes `CREATE DATABASE restaurant` and full schema

## Structure

- `common.css`: common stylesheet
- `config.php`: DB connection, data seeding, auth/register/logout, membership level
- `if.php`: business functions (orders/reservations/inventory/redemption)
- `login.php` / `register.php` / `logout.php`
- User pages: `user_order.php`, `user_reservation.php`, `user_redeem.php`, `user_history.php`
- Staff pages: `staff_orders.php`, `staff_reservations.php`, `staff_materials.php`, `staff_profile.php`
- `restaurant.sql`: schema with default DB `restaurant`
- `dump/bigdump.php`: optional big import helper
- `user.css` / `staff.css`: stylesheets

## Demo Accounts

- User: `user / 123456` (register first if absent)
- Staff: `staff / 123456` (auto inserted by `config.php`)

## Key Notes

- Membership and points: `config.php:251` computes level; points accrue in `if.php:291`
- Order aggregation and status: `if.php:66` summarizes items; `if.php:451` updates status and reduces inventory on completion
- Reservation occupancy check: `if.php:210`
- Points redemption and exchange records: `if.php:409`
- Avatar stored in `LONGBLOB` (`restaurant.sql:169`), UI in `staff_profile.php`

## Caveats

- Passwords are compared as numeric strings, without hashing/salting; demo only
- `cancel_reservation.php` references `getReservationDetails`, not implemented in `if.php`; implement if needed
- No automated tests or deployment scripts included
