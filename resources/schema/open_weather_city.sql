CREATE TABLE city
(
    id        INTEGER NOT NULL,
    name      TEXT    NOT NULL,
    country   TEXT    NOT NULL,
    latitude  REAL    NOT NULL,
    longitude REAL    NOT NULL,
    PRIMARY KEY ("id")
) WITHOUT ROWID;
