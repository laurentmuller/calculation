CREATE TABLE IF NOT EXISTS city
(
    id       INTEGER NOT NULL,
    zip      INTEGER NOT NULL,
    name     TEXT    NOT NULL,
    state_id TEXT    NOT NULL,
    PRIMARY KEY ("id")
) WITHOUT ROWID;

CREATE TABLE "state"
(
    id   TEXT NOT NULL,
    name TEXT NOT NULL,
    PRIMARY KEY (id)
) WITHOUT ROWID;

CREATE TABLE IF NOT EXISTS street
(
    city_id INTEGER NOT NULL,
    name    TEXT    NOT NULL,
    FOREIGN KEY (city_id) REFERENCES city (id)
);
