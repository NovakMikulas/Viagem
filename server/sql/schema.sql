CREATE EXTENSION IF NOT EXISTS postgis;

CREATE TABLE IF NOT EXISTS cadastral_units (
    code         INTEGER PRIMARY KEY,
    name         TEXT NOT NULL,
    geom         geometry(MultiPolygon, 4326),
    imported_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Pro databáze založené před přidáním hranic k.ú.
ALTER TABLE cadastral_units ADD COLUMN IF NOT EXISTS geom geometry(MultiPolygon, 4326);

CREATE TABLE IF NOT EXISTS parcels (
    id                   BIGINT PRIMARY KEY,
    cadastral_unit_code  INTEGER NOT NULL,
    label                TEXT NOT NULL,
    area                 INTEGER,
    land_type            TEXT,
    valid_from           TIMESTAMPTZ,
    geom                 geometry(MultiPolygon, 4326) NOT NULL
);

CREATE INDEX IF NOT EXISTS parcels_geom_idx ON parcels USING GIST (geom);
CREATE INDEX IF NOT EXISTS parcels_cadastral_unit_idx ON parcels (cadastral_unit_code);
