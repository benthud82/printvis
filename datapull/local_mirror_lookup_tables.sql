-- Local-dev-only lookup tables, created to remove the documented fallback
-- warnings in forecast_casepick.py (PRED_GRID_SQL join). Values are not
-- guessed: hour range and equip list are taken directly from the script's
-- own fallback constants (forecast_casepick.py:114-115, 742, 820) and the
-- prod-changes note in sql/PROD_DB_CHANGES_mysql56.md (#002, hour 17 added).
-- Functionally a no-op for forecast output -- the fallback already produces
-- the same hours/equip list -- this just matches prod's table shape locally.
-- NOT copied from prod; structure only, not a substitute for the prod table.

CREATE TABLE IF NOT EXISTS printvis.forecasthours (
    hour_hour INT NOT NULL,
    PRIMARY KEY (hour_hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO printvis.forecasthours (hour_hour)
VALUES (6),(7),(8),(9),(10),(11),(12),(13),(14),(15),(16),(17);

CREATE TABLE IF NOT EXISTS printvis.caseforecast_equip (
    equip_type VARCHAR(20) NOT NULL,
    PRIMARY KEY (equip_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO printvis.caseforecast_equip (equip_type)
VALUES ('ORDERPICKER'),('BELTLINE'),('PALLETJACK');
