-- #! sqlite

-- # { players
-- # { init
CREATE TABLE IF NOT EXISTS players
(
    name TEXT PRIMARY KEY,
    ip TEXT,
    faction TEXT,
    chatmode TEXT,
    drawscoreboard INTEGER,
    mails TEXT
);
-- # }

-- # { create
-- #    :name string
-- #    :ip string
-- #    :faction string
-- #    :chatmode string
-- #    :drawscoreboard int
-- #    :mails string
INSERT INTO players VALUES (:name, :ip, :faction, :chatmode, :drawscoreboard, :mails);
-- # }

-- # { load
SELECT * FROM players;
-- # }

-- # { update
-- #    :ip string
-- #    :faction string
-- #    :chatmode string
-- #    :drawscoreboard int
-- #    :mails string
-- #    :name string
UPDATE players SET ip = :ip, faction = :faction, chatmode = :chatmode, drawscoreboard = :drawscoreboard, mails = :mails WHERE name = :name;
-- # }

-- # { delete
-- #    :name string
DELETE FROM players WHERE name = :name;
-- # }

-- # { drop
DROP TABLE IF EXISTS players;
-- # }
-- # }

-- # { factions
-- # { init
CREATE TABLE IF NOT EXISTS factions
(
    name TEXT PRIMARY KEY,
    owner TEXT,
    color INTEGER,
    roles TEXT
);
-- # }

-- # { create
-- #    :name string
-- #    :owner string
-- #    :color int
-- #    :roles string
INSERT INTO factions VALUES (:name, :owner, :color, :roles);
-- # }

-- # { load
SELECT * FROM factions;
-- # }

-- # { update
-- #    :owner string
-- #    :color int
-- #    :name string
UPDATE factions SET owner = :owner, color = :color, roles = :roles WHERE name = :name;
-- # }

-- # { delete
-- #    :name string
DELETE FROM factions WHERE name = :name;
-- # }

-- # { drop
DROP TABLE IF EXISTS factions;
-- # }
-- # }

-- # { mails
-- # { init
CREATE TABLE IF NOT EXISTS factions
(
    name TEXT PRIMARY KEY,
    owner TEXT,
    color INTEGER,
    roles TEXT
);
-- # }