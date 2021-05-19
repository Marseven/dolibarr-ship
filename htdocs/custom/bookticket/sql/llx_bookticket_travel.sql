-- ============================================================================
-- Copyright (C) 2021 Mebodo Aristide <mebodoaristide@gmail.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- Table of 'travel' for accountancy expert module
-- ============================================================================

create table llx_bookticket_travel
(
  rowid                     bigint AUTO_INCREMENT PRIMARY KEY,
  ref                       varchar(128) DEFAULT 0 NOT NULL,
  jour                      date NOT NULL,
  hour                      time NOT NULL,
  lieu_depart				        varchar(255) DEFAULT NULL,
  lieu_arrive				        varchar(255) DEFAULT NULL,
  fk_ship				            integer DEFAULT NULL,
  entity                    integer default 1,                    
  date_creation             datetime,                    
  tms                       timestamp,                   
  import_key                varchar(32),                 
  status                    smallint DEFAULT 1,
  fk_user_creat             integer      DEFAULT NULL,
  fk_user_modif             integer      DEFAULT NULL,
  active                    tinyint      DEFAULT 1  NOT NULL
)ENGINE=innodb;
