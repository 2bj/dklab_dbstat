CREATE TABLE setting(
	name VARCHAR(32) NOT NULL,
	value VARCHAR(250)
);
CREATE UNIQUE INDEX u_setting_name ON setting(name);
