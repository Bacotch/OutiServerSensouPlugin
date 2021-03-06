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
    INTEGER
    NOT
    NULL
    DEFAULT
    -
    1,
    chatmode
    INTEGER
    NOT
    NULL
    DEFAULT
    -
    1,
    drawscoreboard
    INTEGER
    NOT
    NULL
    DEFAULT
    1,
    roles
    TEXT
    NOT
    NULL
    DEFAULT
    'a:0:{}',
    punishment
    INTEGER
    NOT
    NULL
    DEFAULT
    0,
    discord_userid
    TEXT
    DEFAULT
    NULL
);
-- # }

-- # { create
-- #    :xuid string
-- #    :name string
-- #    :ip string
INSERT INTO players (xuid, name, ip)
VALUES (:xuid,
        :name,
        :ip);
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
    NULL,
    safe
    INTEGER
    NOT
    NULL,
    invites
    TEXT
    NOT
    NULL
    DEFAULT
    'a:0:{}'
);
-- # }

-- # { create
-- #    :name string
-- #    :owner_xuid string
-- #    :color int
-- #    :money int
-- #    :safe int
INSERT INTO factions (name, owner_xuid, color, money, safe)
VALUES (:name, :owner_xuid, :color, :money, :safe);
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
-- #    :safe int
-- #    :invites string
-- #    :id int
UPDATE factions
SET name       = :name,
    owner_xuid = :owner_xuid,
    color      = :color,
    money      = :money,
    safe       = :safe,
    invites    = :invites
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
    sendmail_all_faction_player
    INTEGER
    NOT
    NULL,
    freand_faction_manager
    INTEGER
    NOT
    NULL,
    member_manager
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
-- #    :sendmail_all_faction_player int
-- #    :freand_faction_manager int
-- #    :member_manager int
-- #    :land_manager int
-- #    :bank_manager int
-- #    :role_manager int
INSERT INTO roles (faction_id, name, color, position, sensen_hukoku, sendmail_all_faction_player,
                   freand_faction_manager, member_manager, land_manager, bank_manager, role_manager)
VALUES (:faction_id, :name, :color, :position, :sensen_hukoku, :sendmail_all_faction_player,
        :freand_faction_manager, :member_manager, :land_manager, :bank_manager, :role_manager);
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
-- #    :position int
-- #    :sensen_hukoku int
-- #    :sendmail_all_faction_player int
-- #    :freand_faction_manager int
-- #    :member_manager int
-- #    :land_manager int
-- #    :bank_manager int
-- #    :role_manager int
-- #    :id int
UPDATE roles
SET name                        = :name,
    color                       = :color,
    position                    = :position,
    sensen_hukoku               = :sensen_hukoku,
    sendmail_all_faction_player = :sendmail_all_faction_player,
    freand_faction_manager      = :freand_faction_manager,
    member_manager              = :member_manager,
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

-- # { wars
-- # { init
CREATE TABLE IF NOT EXISTS wars
(
    id
    INTEGER
    PRIMARY
    KEY
    AUTOINCREMENT,
    declaration_faction_id
    INTEGER
    NOT
    NULL,
    enemy_faction_id
    INTEGER
    NOT
    NULL,
    war_type
    INTEGER
    DEFAULT
    NULL,
    start_day
    INTEGER
    DEFAULT
    NULL,
    start_hour
    INTEGER
    DEFAULT
    NULL,
    start_minutes
    INTEGER
    DEFAULT
    NULL,
    started
    INTEGER
    NOT
    NULL
    DEFAULT
    0
);
-- # }

-- # { create
-- #    :declaration_faction_id int
-- #    :enemy_faction_id int
INSERT INTO wars (declaration_faction_id, enemy_faction_id)
VALUES (:declaration_faction_id, :enemy_faction_id);
-- # }

-- # { seq
SELECT seq
FROM sqlite_sequence
WHERE name = 'wars';
-- # }

-- # { load
SELECT *
FROM wars;
-- # }


-- # { update
-- #    :declaration_faction_id int
-- #    :enemy_faction_id int
-- #    :war_type ?int
-- #    :start_day ?int
-- #    :start_hour ?int
-- #    :start_minutes ?int
-- #    :started int
-- #    :id int
UPDATE wars
SET declaration_faction_id = :declaration_faction_id,
    enemy_faction_id       = :enemy_faction_id,
    war_type               = :war_type,
    start_day            = :start_day,
    start_hour           = :start_hour,
    start_minutes        = :start_minutes,
    started                = :started
WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE
FROM wars
WHERE id = :id;
-- # }

-- # { drop
DROP TABLE IF EXISTS wars;
-- # }
-- # }

-- # { war_historys
-- # { init
CREATE TABLE IF NOT EXISTS war_historys
(
    id
    INTEGER
    PRIMARY
    KEY
    AUTOINCREMENT,
    winner_faction_id
    INTEGER
    NOT
    NULL,
    loser_faction_id
    INTEGER
    NOT
    NULL,
    time
    INTEGER
    NOT
    NULL
);
-- # }

-- # { create
-- #    :winner_faction_id int
-- #    :loser_faction_id int
-- #    :time int
INSERT INTO war_historys (winner_faction_id, loser_faction_id, time)
VALUES (:winner_faction_id, :loser_faction_id, :time);
-- # }

-- # { seq
SELECT seq
FROM sqlite_sequence
WHERE name = 'war_historys';
-- # }

-- # { load
SELECT *
FROM war_historys;
-- # }

-- # { update
-- #    :winner_faction_id int
-- #    :loser_faction_id int
-- #    :time int
-- #    :id int
UPDATE war_historys
SET winner_faction_id = :winner_faction_id,
    loser_faction_id  = :loser_faction_id,
    time              = :time
WHERE id = :id;
-- # }

-- # { delete
-- #    :id int
DELETE
FROM war_historys
WHERE id = :id;
-- # }

-- # { drop
DROP TABLE IF EXISTS war_historys;
-- # }
-- # }

-- # }