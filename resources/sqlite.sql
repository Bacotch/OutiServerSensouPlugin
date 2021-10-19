-- #! sqlite

-- # { players
-- # { init
CREATE TABLE IF NOT EXISTS players
(
    name TEXT PRIMARY KEY,
    ip TEXT,
    faction TEXT,
    chatmode TEXT,
    drawscoreboard INTEGER
);
-- # }

-- # { create
-- #    :name string
-- #    :ip string
-- #    :faction string
-- #    :chatmode string
-- #    :drawscoreboard int
INSERT INTO players VALUES (:name, :ip, :faction, :chatmode, :drawscoreboard);
-- # }

-- # { load
SELECT * FROM players;
-- # }

-- # { update
-- #    :ip string
-- #    :faction string
-- #    :chatmode string
-- #    :drawscoreboard int
-- #    :name string
UPDATE players SET ip = :ip, faction = :faction, chatmode = :chatmode, drawscoreboard = :drawscoreboard WHERE name = :name;
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
CREATE TABLE IF NOT EXISTS mails
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    title TEXT,
    content TEXT,
    author TEXT,
    date TEXT,
    read INTEGER
);
-- # }

-- # { create
-- #    :name string
-- #    :title string
-- #    :content string
-- #    :author string
-- #    :date string
INSERT INTO mails (name, title, content, author, date, read) VALUES (:name, :title, :content, :author, :date, 0);
-- # }

-- # { seq
SELECT seq FROM sqlite_sequence WHERE name = 'mails';
-- # }

-- # { load
SELECT * FROM mails;
-- # }

-- # { update
-- #    :read int
-- #    :id int
UPDATE mails SET read = :read WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE FROM mails WHERE id = :id;
-- # }

-- # { drop
DROP TABLE IF EXISTS mails;
-- # }
-- # }