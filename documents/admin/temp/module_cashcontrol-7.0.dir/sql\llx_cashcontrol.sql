-- Copyright (C) 2018 SuperAdmin
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see http://www.gnu.org/licenses/.


CREATE TABLE llx_cashcontrol(
	-- BEGIN MODULEBUILDER FIELDS
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
	entity INTEGER DEFAULT 1 NOT NULL,
	label VARCHAR(255),
	opening double(24,8) default 0,
	cash double(24,8) default 0,
	card double(24,8) default 0,
	status INTEGER,
	date_creation DATETIME NOT NULL,
	date_close DATETIME,
	fk_user_valid integer,
	tms TIMESTAMP NOT NULL,
	import_key VARCHAR(14)
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;