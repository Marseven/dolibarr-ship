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

create table llx_bookticket_penalite
(
  rowid                     bigint AUTO_INCREMENT PRIMARY KEY,
  ref                       varchar(128) DEFAULT 0 NOT NULL,
  datea                     varchar(255) DEFAULT NULL,
  prix_da                   float DEFAULT 5000,
  dateb                     varchar(255) DEFAULT NULL,
  prix_db                   float DEFAULT 8000,
  nom                       varchar(255) DEFAULT NULL,
  prix_n                    float DEFAULT 8000,
  billet_perdu              varchar(255) DEFAULT NULL,
  prix_bp                   float DEFAULT 8000,
  classe                    varchar(255) DEFAULT NULL,
  prix_c                    float DEFAULT NULL,
  classe_enfant             varchar(255) DEFAULT NULL,
  prix_ce                   float DEFAULT NULL,
  fk_bticket				        integer DEFAULT NULL,
  fk_passenger				        integer DEFAULT NULL,
  fk_valideur               integer      DEFAULT NULL,
  entity                    integer default 1,                    
  date_creation             datetime,                    
  tms                       timestamp,                   
  import_key                varchar(32),                 
  status                    smallint DEFAULT 1 NOT NULL,
  fk_user_creat             integer      DEFAULT NULL,
  fk_user_modif             integer      DEFAULT NULL,
  active                    tinyint      DEFAULT 1  NOT NULL
)ENGINE=innodb;
