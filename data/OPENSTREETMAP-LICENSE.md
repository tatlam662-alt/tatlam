# OpenStreetMap locality suggestions

The file `vietnam_osm_localities_2026-10-18.json` is derived from the Vietnam OpenStreetMap extract `vietnam-261018.osm.pbf`, published by [Geofabrik](https://download.geofabrik.de/asia/vietnam.html) and downloaded on 2026-10-19. The extraction considered named OSM features tagged `place=hamlet`, `place=village`, `place=neighbourhood`, or `place=suburb`. A feature was included only when its point fell inside a mapped administrative boundary whose normalized name matched exactly one current PanDaShop ward record.

These are map-derived suggestions, not an official government register. The importer stores them with `is_verified=0` and labels them as unverified in checkout. `place=hamlet` and `place=village` are placed in the Thôn/Tổ dân phố suggestion field; `place=neighbourhood` and `place=suburb` are placed in the Xóm/Ấp suggestion field. These mappings are approximate and should not be treated as legal administrative classifications.

Explicit locality-name prefixes override the approximate OSM feature mapping during import: Tổ dân phố, TDP, Khu phố, KP, and Thôn go to the main locality field; Xóm, Ấp, Khu dân cư, and Cụm dân cư go to the child locality field. These remain unverified map suggestions. The importer also reclassifies existing OSM rows and deactivates duplicates when the same ward/name already exists in the target field.

**Attribution:** © [OpenStreetMap contributors](https://www.openstreetmap.org/copyright). The OpenStreetMap-derived data is available under the [Open Database License (ODbL) 1.0](https://opendatacommons.org/licenses/odbl/1-0/). The OSM-sourced subset of the locality suggestions database is provided under ODbL 1.0.

Geofabrik provides the regional extract as a convenience; the underlying map data comes from OpenStreetMap contributors. The snapshot date is recorded in the dataset filename and should be refreshed when importing a newer extract.
