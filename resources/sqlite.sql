-- #! sqlite

-- # { init
CREATE TABLE IF NOT EXISTS factions
(
    id            VARCHAR(36) PRIMARY KEY,
    name          TEXT,
    creation_time INTEGER,
    description   TEXT,
    motd          TEXT,
    members       TEXT,
    permissions   TEXT,
    flags         TEXT,
    home          TEXT,
    relations     TEXT,
    banned        TEXT,
    money         FLOAT DEFAULT 0,
    powerboost    FLOAT DEFAULT 0
    );
-- # }