CREATE TABLE data(
	id INTEGER,
	item_id INTEGER,
	value TEXT,
	created INTEGER
);
CREATE UNIQUE INDEX u_data_id ON data(id);
CREATE INDEX u_data_created ON data(created);
CREATE UNIQUE INDEX u_item_name ON item(name);
