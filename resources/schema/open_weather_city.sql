CREATE TABLE IF NOT EXISTS city
(
    id        INTEGER  PRIMARY KEY,
    name      TEXT    NOT NULL,
    country   TEXT    NOT NULL,
    latitude  REAL    NOT NULL,
    longitude REAL    NOT NULL
) WITHOUT ROWID;
