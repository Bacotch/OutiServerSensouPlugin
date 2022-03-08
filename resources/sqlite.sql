-- #! sqlite

-- # { outiserver
-- # { players
-- # { init
CREATE TABLE IF NOT EXISTS players
(
    xuid
    TEXT
    PRIMARY
    KEY,
    name
    TEXT
    NOT
    NULL,
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
    NOT
    NULL,
    punishment
    INTEGER
    NOT
    NULL,
    money
    INTEGER
    NOT
    NULL,
    discord_userid
    TEXT
);
-- # }

-- # { create
-- #    :xuid string
-- #    :name string
-- #    :ip string
-- #    :money int
INSERT INTO players
VALUES (:xuid,
        :name,
        :ip,
        -1,
        -1,
        1,
        "a:0:{}",
        0,
        :money,
        NULL);
-- # }

-- # { load
SELECT *
FROM players;
-- # }

-- # { update
-- #    :name string
-- #    :ip string
-- #    :faction int
-- #    :chatmode int
-- #    :drawscoreboard int
-- #    :roles string
-- #    :punishment int
-- #    :money int
-- #    :discord_userid ?string
-- #    :xuid string
UPDATE players
SET name           = :name,
    ip             = :ip,
    faction        = :faction,
    chatmode       = :chatmode,
    drawscoreboard = :drawscoreboard,
    roles          = :roles,
    punishment     = :punishment,
    money          = :money,
    discord_userid = :discord_userid
WHERE xuid = :xuid;
-- # }

-- # { delete
-- #    :xuid string
DELETE
FROM players
WHERE xuid = :xuid;
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
    owner_xuid
    TEXT
    NOT
    NULL,
    color
    INTEGER
    NOT
    NULL,
    money
    INTEGER
    NOT
    NULL
);
-- # }

-- # { create
-- #    :name string
-- #    :owner_xuid string
-- #    :color int
-- #    :money int
INSERT INTO factions (name, owner_xuid, color, money)
VALUES (:name, :owner_xuid, :color, :money);
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
-- #    :owner_xuid string
-- #    :color int
-- #    :money int
-- #    :id int
UPDATE factions
SET name       = :name,
    owner_xuid = :owner_xuid,
    color      = :color,
    money      = :money
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
    sendto_xuid
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
    author_xuid
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
-- #    :sendto_xuid string
-- #    :title string
-- #    :content string
-- #    :author_xuid string
-- #    :date string
INSERT INTO mails (sendto_xuid, title, content, author_xuid, date, read)
VALUES (:sendto_xuid, :title, :content, :author_xuid, :date, 0);
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
-- #    :title string
-- #    :content string
-- #    :read int
-- #    :id int
UPDATE mails
SET title   = :title,
    content = :content,
    read    = :read
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
    position
    INTEGER
    NOT
    NULL,
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
                   freand_faction_manager, kick_faction_player, land_manager, bank_manager, role_manager)
VALUES (:faction_id, :name, :color, :position, :sensen_hukoku, :invite_player, :sendmail_all_faction_player,
        :freand_faction_manager, :kick_faction_player, :land_manager, :bank_manager, :role_manager);
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
-- #     :position int
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
    position                    = :position,
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
-- #    :defaultperms string
-- #    :roleperms string
-- #    :memberperms string
-- #    :id int
UPDATE landconfigs
SET defaultperms = :defaultperms,
    roleperms    = :roleperms,
    memberperms  = :memberperms
WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE
FROM landconfigs
WHERE id = :id;
-- # }

-- # { drop
DROP TABLE IF EXISTS landconfigs;
-- # }
-- # }

-- # { schedulemessages
-- # { init
CREATE TABLE IF NOT EXISTS schedulemessages
(
    id
    INTEGER
    PRIMARY
    KEY
    AUTOINCREMENT,
    content
    TEXT
    NOT
    NULL
);
-- # }

-- # { create
-- #    :content string
INSERT INTO schedulemessages (content)
VALUES (:content);
-- # }

-- # { seq
SELECT seq
FROM sqlite_sequence
WHERE name = 'schedulemessages';
-- # }

-- # { load
SELECT *
FROM schedulemessages;
-- # }

-- # { update
-- #    :content string
-- #    :id int
UPDATE schedulemessages
SET content = :content
WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE
FROM schedulemessages
WHERE id = :id;
-- # }

-- # { drop
DROP TABLE IF EXISTS schedulemessages;
-- # }
-- # }

-- # { chestshops
-- # { init
CREATE TABLE IF NOT EXISTS chestshops
(
    id
    INTEGER
    PRIMARY
    KEY
    AUTOINCREMENT,
    owner_xuid
    TEXT
    NOT
    NULL,
    faction_id
    INTEGER
    NOT
    NULL,
    worldname
    TEXT
    NOT
    NULL,
    chestx
    INTEGER
    NOT
    NULL,
    chesty
    INTEGER
    NOT
    NULL,
    chestz
    INTEGER
    NOT
    NULL,
    signboardx
    INTEGER
    NOT
    NULL,
    signboardy
    INTEGER
    NOT
    NULL,
    signboardz
    INTEGER
    NOT
    NULL,
    itemid
    INTEGER
    NOT
    NULL,
    itemmeta
    INTEGER
    NOT
    NULL,
    price
    INTEGER
    NOT
    NULL,
    duty
    INTEGER
    NOT
    NULL
);
-- # }

-- # { create
-- #    :owner_xuid string
-- #    :faction_id int
-- #    :worldname string
-- #    :chestx int
-- #    :chesty int
-- #    :chestz int
-- #    :signboardx int
-- #    :signboardy int
-- #    :signboardz int
-- #    :itemid int
-- #    :itemmeta int
-- #    :price int
-- #    :duty int
INSERT INTO chestshops (owner_xuid, faction_id, worldname, chestx, chesty, chestz, signboardx, signboardy, signboardz,
                        itemid,
                        itemmeta, price, duty)
VALUES (:owner_xuid, :faction_id, :worldname, :chestx, :chesty, :chestz, :signboardx, :signboardy, :signboardz, :itemid,
        :itemmeta,
        :price, :duty);
-- # }

-- # { seq
SELECT seq
FROM sqlite_sequence
WHERE name = 'chestshops';
-- # }

-- # { load
SELECT *
FROM chestshops;
-- # }

-- # { update
-- #    :itemid int
-- #    :itemmeta int
-- #    :price int
-- #    :duty int
-- #    :id int
UPDATE chestshops
SET itemid   = :itemid,
    itemmeta = :itemmeta,
    price    = :price,
    duty     = :duty
WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE
FROM chestshops
WHERE id = :id;
-- # }

-- # { drop
DROP TABLE IF EXISTS chestshops;
-- # }
-- # }

-- # { adminshops
-- # { init
CREATE TABLE IF NOT EXISTS adminshops
(
    id
    INTEGER
    PRIMARY
    KEY
    AUTOINCREMENT,
    item_id
    INTEGER
    NOT
    NULL,
    item_meta
    INTEGER
    NOT
    NULL,
    min_price
    INTEGER
    NOT
    NULL,
    max_price
    INTEGER
    NOT
    NULL,
    price
    INTEGER
    NOT
    NULL,
    default_price
    INTEGER
    NOT
    NULL,
    rate_count
    INTEGER
    NOT
    NULL,
    rate_fluctuation
    INTEGER
    NOT
    NULL,
    sell_count
    INTEGER
    NOT
    NULL
    DEFAULT
    0
);
-- # }

-- # { create
-- #    :item_id int
-- #    :item_meta int
-- #    :min_price int
-- #    :max_price int
-- #    :default_price int
-- #    :rate_count int
-- #    :rate_fluctuation int
INSERT INTO adminshops (item_id, item_meta, min_price, max_price, price, default_price, rate_count, rate_fluctuation)
VALUES (:item_id, :item_meta, :min_price, :max_price, :default_price, :default_price, :rate_count, :rate_fluctuation)
-- # }

-- # { seq
SELECT seq
FROM sqlite_sequence
WHERE name = 'adminshops';
-- # }

-- # { load
SELECT *
FROM adminshops;
-- # }

-- # { update
-- #    :item_id int
-- #    :item_meta int
-- #    :min_price int
-- #    :max_price int
-- #    :price int
-- #    :default_price int
-- #    :rate_count int
-- #    :rate_fluctuation int
-- #    :sell_count int
-- #    :id int
UPDATE adminshops
SET item_id          = :item_id,
    item_meta        = :item_meta,
    min_price        = :min_price,
    max_price        = :max_price,
    price            = :price,
    default_price    = :default_price,
    rate_count       = :rate_count,
    rate_fluctuation = :rate_fluctuation,
    sell_count       = :sell_count
WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE
FROM adminshops
WHERE id = :id;
-- # }

-- # { drop
DROP TABLE IF EXISTS adminshops;
-- # }
-- # }
-- # }