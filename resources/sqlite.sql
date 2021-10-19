-- #! sqlite

-- # { players
-- # { init
CREATE TABLE IF NOT EXISTS players
(
    name TEXT PRIMARY KEY,
    ip TEXT,
    faction INTEGER,
    chatmode INTEGER,
    drawscoreboard INTEGER
);
-- # }

-- # { create
-- #    :name string
-- #    :ip string
-- #    :drawscoreboard int
INSERT INTO players VALUES (:name, :ip, -1, -1, :drawscoreboard);
-- # }

-- # { load
SELECT * FROM players;
-- # }

-- # { update
-- #    :ip string
-- #    :faction int
-- #    :chatmode int
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
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    owner TEXT,
    color INTEGER
);
-- # }

-- # { create
-- #    :name string
-- #    :owner string
-- #    :color int
INSERT INTO factions (name, owner, color) VALUES (:name, :owner, :color);
-- # }

-- # { seq
SELECT seq FROM sqlite_sequence WHERE name = 'factions';
-- # }

-- # { load
SELECT * FROM factions;
-- # }

-- # { update
-- #    :name string
-- #    :owner string
-- #    :color int
-- #    :id int
UPDATE factions SET name = :name, owner = :owner, color = :color WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE FROM factions WHERE id = :id;
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

-- # { faction_roles
-- # { init
CREATE TABLE IF NOT EXISTS faction_roles
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    faction_id INTEGER,
    name TEXT,
    sensen_hukoku INTEGER,
    invite_player INTEGER,
    sendmail_all_faction_player INTEGER,
    freand_faction_manager INTEGER,
    kick_faction_player INTEGER,
    land_manager INTEGER,
    bank_manager INTEGER,
    role_manager INTEGER
);
-- # }

-- # { create
-- #    :faction_id string
-- #    :name string
-- #    :sensen_hukoku int
-- #    :invite_player int
-- #    :sendmail_all_faction_player int
-- #    :freand_faction_manager int
-- #    :kick_faction_player int
-- #    :land_manager int
-- #    :bank_manager int
-- #    :role_manager int
INSERT INTO faction_roles (faction_id, name, sensen_hukoku, invite_player, sendmail_all_faction_player, freand_faction_manager, kick_faction_player, land_manager, bank_manager, role_manager) VALUES (:faction_id, :name, :sensen_hukoku, :invite_player, :sendmail_all_faction_player, :freand_faction_manager, :kick_faction_player, :land_manager, :bank_manager, :role_manager);
-- # }

-- # { seq
SELECT seq FROM sqlite_sequence WHERE name = 'faction_roles';
-- # }

-- # { load
SELECT * FROM faction_roles;
-- # }

-- # { update
-- #    :name string
-- #    :sensen_hukoku int
-- #    :invite_player int
-- #    :sendmail_all_faction_player int
-- #    :freand_faction_manager int
-- #    :kick_faction_player int
-- #    :land_manager int
-- #    :bank_manager int
-- #    :role_manager int
-- #    :id int
UPDATE faction_roles SET name = :name, sensen_hukoku = :sensen_hukoku, invite_player = :invite_player, sendmail_all_faction_player = :sendmail_all_faction_player, freand_faction_manager = :freand_faction_manager, kick_faction_player = :kick_faction_player, land_manager = :land_manager, bank_manager = :bank_manager, role_manager = :role_manager WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE FROM faction_roles WHERE id = :id;
-- # }

-- # { drop
DROP TABLE IF EXISTS faction_roles;
-- # }
-- # }