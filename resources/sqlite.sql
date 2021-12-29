-- #! sqlite

-- # { outiserver
-- # { players
-- # { init
CREATE TABLE IF NOT EXISTS players
(
    name
    TEXT
    PRIMARY
    KEY,
    ip
    TEXT
    NOT
    NULL,
    faction
    INTEGER,
    chatmode
    INTEGER,
    drawscoreboard
    INTEGER
    NOT
    NULL,
    roles
    TEXT
);
-- # }

-- # { create
-- #    :name string
-- #    :ip string
-- #    :drawscoreboard int
INSERT INTO players
VALUES (:name,
        :ip,
        -1,
        -1,
        :drawscoreboard,
        "a:0:{}");
-- # }

-- # { load
SELECT *
FROM players;
-- # }

-- # { update
-- #    :ip string
-- #    :faction int
-- #    :chatmode int
-- #    :drawscoreboard int
-- #    :roles string
-- #    :name string
UPDATE players
SET ip             = :ip,
    faction        = :faction,
    chatmode       = :chatmode,
    drawscoreboard = :drawscoreboard,
    roles          = :roles
WHERE name = :name;
-- # }

-- # { delete
-- #    :name string
DELETE
FROM players
WHERE name = :name;
-- # }

-- # { drop
DROP TABLE IF EXISTS players;
-- # }
-- # }

-- # { factions
-- # { init
CREATE TABLE IF NOT EXISTS factions
(
    id
    INTEGER
    PRIMARY
    KEY
    AUTOINCREMENT,
    name
    TEXT
    NOT
    NULL,
    owner
    TEXT
    NOT
    NULL,
    color
    INTEGER
    NOT
    NULL
);
-- # }

-- # { create
-- #    :name string
-- #    :owner string
-- #    :color int
INSERT INTO factions (name, owner, color)
VALUES (:name, :owner, :color);
-- # }

-- # { seq
SELECT seq
FROM sqlite_sequence
WHERE name = 'factions';
-- # }

-- # { load
SELECT *
FROM factions;
-- # }

-- # { update
-- #    :name string
-- #    :owner string
-- #    :color int
-- #    :id int
UPDATE factions
SET name  = :name,
    owner = :owner,
    color = :color
WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE
FROM factions
WHERE id = :id;
-- # }

-- # { drop
DROP TABLE IF EXISTS factions;
-- # }
-- # }

-- # { mails
-- # { init
CREATE TABLE IF NOT EXISTS mails
(
    id
    INTEGER
    PRIMARY
    KEY
    AUTOINCREMENT,
    name
    TEXT
    NOT
    NULL,
    title
    TEXT
    NOT
    NULL,
    content
    TEXT
    NOT
    NULL,
    author
    TEXT
    NOT
    NULL,
    date
    TEXT
    NOT
    NULL,
    read
    INTEGER
    NOT
    NULL
);
-- # }

-- # { create
-- #    :name string
-- #    :title string
-- #    :content string
-- #    :author string
-- #    :date string
INSERT INTO mails (name, title, content, author, date, read)
VALUES (:name, :title, :content, :author, :date, 0);
-- # }

-- # { seq
SELECT seq
FROM sqlite_sequence
WHERE name = 'mails';
-- # }

-- # { load
SELECT *
FROM mails;
-- # }

-- # { update
-- #    :read int
-- #    :id int
UPDATE mails
SET read = :read
WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE
FROM mails
WHERE id = :id;
-- # }

-- # { drop
DROP TABLE IF EXISTS mails;
-- # }
-- # }

-- # { roles
-- # { init
CREATE TABLE IF NOT EXISTS roles
(
    id
    INTEGER
    PRIMARY
    KEY
    AUTOINCREMENT,
    faction_id
    INTEGER
    NOT
    NULL,
    name
    TEXT
    NOT
    NULL,
    color
    INTEGER
    NOT
    NULL,
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> efe6a50 (土地保護詳細設定進捗)
    position
    INTEGER
    NOT
    NULL,
<<<<<<< HEAD
=======
>>>>>>> 3698ba6 (土地系進捗)
=======
>>>>>>> efe6a50 (土地保護詳細設定進捗)
    sensen_hukoku
    INTEGER
    NOT
    NULL,
    invite_player
    INTEGER
    NOT
    NULL,
    sendmail_all_faction_player
    INTEGER
    NOT
    NULL,
    freand_faction_manager
    INTEGER
    NOT
    NULL,
    kick_faction_player
    INTEGER
    NOT
    NULL,
    land_manager
    INTEGER
    NOT
    NULL,
    bank_manager
    INTEGER
    NOT
    NULL,
    role_manager
    INTEGER
    NOT
    NULL
);
-- # }

-- # { create
-- #    :faction_id int
-- #    :name string
-- #    :color int
-- #    :position int
-- #    :sensen_hukoku int
-- #    :invite_player int
-- #    :sendmail_all_faction_player int
-- #    :freand_faction_manager int
-- #    :kick_faction_player int
-- #    :land_manager int
-- #    :bank_manager int
-- #    :role_manager int
INSERT INTO roles (faction_id, name, color, position, sensen_hukoku, invite_player, sendmail_all_faction_player,
                   freand_faction_manager, kick_faction_player, land_manager, bank_manager, role_manager);
-- # }

-- # { seq
SELECT seq
FROM sqlite_sequence
WHERE name = 'roles';
-- # }

-- # { load
SELECT *
FROM roles;
-- # }

-- # { update
-- #    :name string
-- #    :color int
-- #    :sensen_hukoku int
-- #    :invite_player int
-- #    :sendmail_all_faction_player int
-- #    :freand_faction_manager int
-- #    :kick_faction_player int
-- #    :land_manager int
-- #    :bank_manager int
-- #    :role_manager int
-- #    :id int
UPDATE roles
SET name                        = :name,
    color                       = :color,
    sensen_hukoku               = :sensen_hukoku,
    invite_player               = :invite_player,
    sendmail_all_faction_player = :sendmail_all_faction_player,
    freand_faction_manager      = :freand_faction_manager,
    kick_faction_player         = :kick_faction_player,
    land_manager                = :land_manager,
    bank_manager                = :bank_manager,
    role_manager                = :role_manager
WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE
FROM roles
WHERE id = :id;
-- # }

-- # { drop
DROP TABLE IF EXISTS roles;
-- # }
-- # }

-- # { lands
-- # { init
CREATE TABLE IF NOT EXISTS lands
(
    id
    INTEGER
    PRIMARY
    KEY
    AUTOINCREMENT,
    faction_id
    INTEGER
    NOT
    NULL,
    x
    INTEGER
    NOT
    NULL,
    z
    INTEGER
    NOT
    NULL,
    world
    TEXT
    NOT
    NULL
);
-- # }

-- # { create
-- #    :faction_id int
-- #    :x int
-- #    :z int
-- #    :world string
INSERT INTO lands (faction_id,
                   x,
                   z,
                   world)
VALUES (:faction_id,
        :x,
        :z,
        :world);
-- # }

-- # { seq
SELECT seq
FROM sqlite_sequence
WHERE name = 'lands';
-- # }

-- # { load
SELECT *
FROM lands;
-- # }

-- # { update
-- #    :faction_id int
-- #    :x int
-- #    :z int
-- #    :world string
-- #    :id int
UPDATE lands
SET faction_id = :faction_id,
    x          = :x,
    z          = :z,
    world      = :world
WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE
FROM lands
WHERE id = :id;
-- # }

-- # { delete_faction
-- #    :faction_id int
DELETE
FROM lands
WHERE faction_id = :faction_id;
-- # }

-- # { drop
DROP TABLE IF EXISTS lands;
-- # }
-- # }

-- # { landconfigs
-- # { init
CREATE TABLE IF NOT EXISTS landconfigs
(
    id
    INTEGER
    PRIMARY
    KEY
    AUTOINCREMENT,
    landid
    INTEGER
    NOT
    NULL,
    startx
    INTEGER
    NOT
    NULL,
    startz
    INTEGER
    NOT
    NULL,
    endx
    INTEGER
    NOT
    NULL,
    endz
    INTEGER
    NOT
    NULL,
    defaultperms
    TEXT
    NOT
    NULL,
    roleperms
    TEXT
    NOT
    NULL,
    memberperms
    TEXT
    NOT
    NULL
);
-- # }

-- # { create
-- #    :landid int
-- #    :startx int
-- #    :startz int
-- #    :endx int
-- #    :endz int
-- #    :defaultperms string
-- #    :roleperms string
-- #    :memberperms string

INSERT INTO landconfigs (landid,
                         startx,
                         startz,
                         endx,
                         endz,
                         defaultperms,
                         roleperms,
                         memberperms)
VALUES (:landid,
        :startx,
        :startz,
        :endx,
        :endz,
        :defaultperms,
        :roleperms,
        :memberperms);
-- # }

-- # { seq
SELECT seq
FROM sqlite_sequence
WHERE name = 'landconfigs';
-- # }

-- # { load
SELECT *
FROM landconfigs;
-- # }

-- # { update
-- #    :startx int
-- #    :startz int
-- #    :endx int
-- #    :endz int
-- #    :defaultperms string
-- #    :roleperms string
-- #    :memberperms string
-- #    :id int
UPDATE landconfigs
SET startx = :startx,
    startz = :startz,
    endx   = :endx,
    endz   = :endz,
    defaultperms  = :defaultperms,
    roleperms = :roleperms,
    memberperms = :memberperms
WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE
FROM landconfigs
WHERE id = :id;
-- # }

-- # { delete_land
-- #    :landid int
DELETE
FROM landconfigs
WHERE landid = :landid;
-- # }

-- # { drop
DROP TABLE IF EXISTS landconfigs;
-- # }
-- # }
-- # }