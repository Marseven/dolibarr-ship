-- ===========================================================================
-- Copyright (C) 2021 Mebodo Aristide <mebodoaristide@gmail.com>
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

ALTER TABLE llx_bookticket_bticket ADD CONSTRAINT fk_ticket_fk_ship FOREIGN KEY (fk_ship) REFERENCES llx_bookticket_ship (rowid);
ALTER TABLE llx_bookticket_bticket ADD CONSTRAINT fk_ticket_fk_passenger FOREIGN KEY (fk_passenger) REFERENCES llx_bookticket_passenger (rowid);
ALTER TABLE llx_bookticket_bticket ADD CONSTRAINT fk_ticket_fk_passenger_acc FOREIGN KEY (fk_passenger_acc) REFERENCES llx_bookticket_passenger (ref);
ALTER TABLE llx_bookticket_bticket ADD CONSTRAINT fk_ticket_fk_classe FOREIGN KEY (fk_classe) REFERENCES llx_bookticket_classe (rowid);
ALTER TABLE llx_bookticket_bticket ADD CONSTRAINT fk_ticket_fk_travel FOREIGN KEY (fk_travel) REFERENCES llx_bookticket_travel (rowid);
ALTER TABLE llx_bookticket_bticket ADD CONSTRAINT fk_ticket_fk_agence FOREIGN KEY (fk_agence) REFERENCES llx_bookticket_agence (rowid);
