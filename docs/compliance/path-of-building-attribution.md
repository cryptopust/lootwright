# Path of Building Format Attribution

Lootwright independently implements import compatibility with build-code
formats produced by Path of Building Community. The upstream projects are:

- Path of Building (PoE1), commit `bcbca9b60b04abc17935c84ff3589342193bd758`: <https://github.com/PathOfBuildingCommunity/PathOfBuilding>
- Path of Building for Path of Exile 2, commit `5d173cbf8c9cf394a975cbb813f19d0b6dc67ea6`: <https://github.com/PathOfBuildingCommunity/PathOfBuilding-PoE2>

Both root `LICENSE.md` files begin with the MIT license for Path of Building
Community and then reproduce notices for bundled dependencies. Lootwright does
not copy, translate, execute, link, or redistribute upstream Lua code,
executables, dependencies, game data, formulas, assets, or full builds. In
particular, no upstream Base64, XML, compression, or Lua implementation is
included; PHP's built-in facilities are used instead.

The approved use is limited to interoperating with the user-controlled format:
Base64/Base64URL text, a zlib-compressed XML document, the distinct
`PathOfBuilding` and `PathOfBuilding2` root markers, and structural field names
needed to read a build the user deliberately supplies. All test fixtures are
small Lootwright-original documents containing invented identifiers.

Path of Building Community and Grinding Gear Games do not endorse Lootwright.
The upstream MIT license does not grant rights to GGG content or to third-party
material that may appear in a user's build.
