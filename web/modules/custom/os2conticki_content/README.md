# OS2ConTicki content

## Building assets

```sh
yarn install
yarn build
```

## Coding standards

```sh
yarn coding-standards-check
yarn coding-standards-apply
```

## Notes on the Entity Clone module

`patches/entity_clone_extras-drupal-9.patch` contains a slightly modufied
version of the patch
https://www.drupal.org/files/issues/2020-05-19/remove_unnecessary_files-2931143-36.patch
from https://www.drupal.org/node/2931143.

The modified patch changes `+core: 8.x` to `core_version_requirement: ^9` in
`entity_clone_extras.info.yml`.
