-- Local/prod-compatible support tables for the Production page active-picking feed.
-- The live refresh is safe when line-level tables are unavailable, but creating
-- voicepicks restores current-aisle and remaining-time enrichment.

CREATE TABLE IF NOT EXISTS printvis.voicepicks (
    Pick_ID VARCHAR(64) NOT NULL,
    Whse INT NOT NULL,
    Batch_Num VARCHAR(45) NOT NULL,
    Status INT DEFAULT NULL,
    Short_Status TINYINT DEFAULT NULL,
    Location VARCHAR(32) DEFAULT NULL,
    Sect VARCHAR(8) DEFAULT NULL,
    Aisle VARCHAR(8) DEFAULT NULL,
    Bay VARCHAR(8) DEFAULT NULL,
    Lev VARCHAR(8) DEFAULT NULL,
    Pos VARCHAR(8) DEFAULT NULL,
    PickType VARCHAR(32) DEFAULT NULL,
    LotReq TINYINT DEFAULT NULL,
    QtyOrder DECIMAL(15, 4) DEFAULT NULL,
    QtyPick DECIMAL(15, 4) DEFAULT NULL,
    PackageUnit DECIMAL(15, 4) DEFAULT NULL,
    Drug TINYINT DEFAULT NULL,
    Ice TINYINT DEFAULT NULL,
    Haz TINYINT DEFAULT NULL,
    SO TINYINT DEFAULT NULL,
    SN TINYINT DEFAULT NULL,
    NSI TINYINT DEFAULT NULL,
    Ped VARCHAR(8) DEFAULT NULL,
    ExpyChkReq TINYINT DEFAULT NULL,
    ItemCode BIGINT DEFAULT NULL,
    NDC_Num VARCHAR(32) DEFAULT NULL,
    EachWeight DECIMAL(15, 6) DEFAULT NULL,
    DateTimeFirstPick DATETIME DEFAULT NULL,
    DATECREATED DATETIME DEFAULT NULL,
    BO TINYINT DEFAULT NULL,
    PutAwayFlag TINYINT DEFAULT NULL,
    LOCJOIN VARCHAR(16) DEFAULT NULL,
    WCS_NUM BIGINT DEFAULT NULL,
    WORKORDER_NUM INT DEFAULT NULL,
    BOX_NUM INT DEFAULT NULL,
    TOTELOC VARCHAR(45) DEFAULT NULL,
    SHIP_ZONE VARCHAR(45) DEFAULT NULL,
    UserDescription VARCHAR(100) DEFAULT NULL,
    ReserveUSerID INT DEFAULT NULL,
    PRIMARY KEY (Whse, Pick_ID),
    KEY idx_voicepicks_whse_batch (Whse, Batch_Num),
    KEY idx_voicepicks_event (DateTimeFirstPick),
    KEY idx_voicepicks_user (ReserveUSerID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Older copies keyed only on Pick_ID, even though TaskNumber values are local
-- to each Lucas server. Upgrade that key without changing or deleting rows.
SET @voicepicks_composite_pk = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = 'printvis'
      AND table_name = 'voicepicks'
      AND index_name = 'PRIMARY'
      AND ((seq_in_index = 1 AND column_name = 'Whse')
        OR (seq_in_index = 2 AND column_name = 'Pick_ID'))
);
SET @voicepicks_pk_sql = IF(
    @voicepicks_composite_pk = 2,
    'SELECT 1',
    'ALTER TABLE printvis.voicepicks DROP PRIMARY KEY, ADD PRIMARY KEY (Whse, Pick_ID)'
);
PREPARE voicepicks_pk_stmt FROM @voicepicks_pk_sql;
EXECUTE voicepicks_pk_stmt;
DEALLOCATE PREPARE voicepicks_pk_stmt;

CREATE TABLE IF NOT EXISTS printvis.looselines_cartsinprocess_temp
    LIKE printvis.looselines_cartsinprocess;
