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
-- Table of 'ticket' for bookticket module
-- ============================================================================

create table llx_bookticket_ticket
(
  rowid                     bigint AUTO_INCREMENT PRIMARY KEY,
  ref                       varchar(128) DEFAULT 0 NOT NULL,
  barcode                   varchar(128) DEFAULT 0 NOT NULL,
  model_pdf                 varchar(255) DEFAULT NULL,
  entity                    integer default 1,                    
  date_creation             datetime,                    
  tms                       timestamp,                   
  import_key                varchar(32),                 
  status                    smallint DEFAULT 1 NOT NULL,
  fk_ship                   integer      DEFAULT NULL,
  fk_passenger              integer      DEFAULT NULL,
  fk_travel                 integer      DEFAULT NULL,
  fk_classe                 integer      DEFAULT NULL, 
  fk_user_creat             integer      DEFAULT NULL,
  fk_user_modif             integer      DEFAULT NULL,
)ENGINE=innodb;
