-- ===========================================================================
-- Copyright (C) 2008 Author <email@author.com>
-- 
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
-- ===========================================================================

ALTER TABLE llx_matable ADD UNIQUE uk_matable_field(field_one, field_two);

ALTER TABLE llx_matable ADD CONSTRAINT fk_matable_fk_field FOREIGN KEY (fk_field) REFERENCES llx_matablepere (rowid);
