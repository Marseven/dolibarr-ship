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
-- Table of 'classe' for accountancy expert module
-- ============================================================================

create table llx_bookticket_classe
(
  rowid                     bigint AUTO_INCREMENT PRIMARY KEY,
  ref                       varchar(128) DEFAULT 0 NOT NULL,
  label                     varchar(255) NOT NULL,
  labelshort				        varchar(255) DEFAULT NULL,
  prix_standard				      integer DEFAULT NULL,
  prix_enfant				        integer DEFAULT NULL,
  prix_enf_stand				    integer DEFAULT NULL,
  kilo_bagage				        integer DEFAULT NULL,
  entity                    integer default 1,                    
  date_creation             datetime,                    
  tms                       timestamp,                   
  import_key                varchar(32),                 
  status                    smallint DEFAULT 1 NOT NULL, 
  fk_user_creat             integer      DEFAULT NULL,
  fk_user_modif             integer      DEFAULT NULL,
  active                    tinyint      DEFAULT 1  NOT NULL
)ENGINE=innodb;
