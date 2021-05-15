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
-- Table of 'passenger' for accountancy expert module
-- ============================================================================

create table llx_bookticket_passenger
(
  rowid                     bigint AUTO_INCREMENT PRIMARY KEY,
  nom				                varchar(255) DEFAULT NULL,
  prenom				            varchar(255) DEFAULT NULL,
  age				                integer DEFAULT NULL,
  adresse				            varchar(255) DEFAULT NULL,
  telephone				          varchar(255) DEFAULT NULL,
  email				              varchar(255) DEFAULT NULL,
  entity                    integer default 1,                    
  date_creation             datetime,                    
  tms                       timestamp,                   
  import_key                varchar(32),                 
  status                    smallint, 
  fk_accounting_category    integer      DEFAULT 0,			  -- ID of personalized group for report
  fk_user_author            integer      DEFAULT NULL,
  fk_user_modif             integer      DEFAULT NULL,
  active                    tinyint      DEFAULT 1  NOT NULL
)ENGINE=innodb;
