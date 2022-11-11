CREATE TABLE IF NOT EXISTS city
(
    id       INTEGER PRIMARY KEY,
    zip      INTEGER NOT NULL,
    name     TEXT    NOT NULL,
    state_id TEXT    NOT NULL
) WITHOUT ROWID;

CREATE TABLE IF NOT EXISTS state
(
    id   TEXT PRIMARY KEY,
    name TEXT NOT NULL
) WITHOUT ROWID;

CREATE TABLE IF NOT EXISTS street
(
    city_id INTEGER NOT NULL,
    name    TEXT    NOT NULL,
    FOREIGN KEY (city_id) REFERENCES city(id)
);

CREATE INDEX IF NOT EXISTS idx_city_name ON city(name);

CREATE INDEX IF NOT EXISTS idx_zip_name ON city(zip);

CREATE INDEX IF NOT EXISTS idx_street_name ON street(name);
