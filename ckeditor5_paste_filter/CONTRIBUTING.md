# Contribution guidelines

We welcome contributions of all types and sizes! This documentation may look a
bit scary but we don't expect a casual contributor to follow all of these
guidelines or tick all of these boxes. For example, maintainers and other
contributors will help write tests if a bugfix is missing test coverage, and
will rewrite commit messages to follow conventions.

This documentation serves the maintainers as well and outlines what is expected
of current and future maintainers.

See also [RELEASING.md](RELEASING.md).

## Coding standards

We follow the [Drupal coding standards](https://www.drupal.org/docs/develop/standards).

## Compiling JavaScript

From the project root:

```
yarn install # Only required the first time, or if dependencies change.
yarn build
```

There is also `yarn watch`, `yarn lint`, `yarn fix`.

## Test coverage

All bug fixes and new features must have test coverage, we have a suite of
Playwright tests that cover the functionality of the module.

### Running tests

1. Copy .env.defaults to .env (this file will be ignored by git)
2. Update `DRUPAL_TEST_BASE_URL` to point to your test site
3. Run `yarn install` if you haven't already
4. Run `yarn test`

You may want to run tests via DDEV, the maintainers use DDEV and the Lullabot
[ddev-playwright](https://github.com/Lullabot/ddev-playwright) add-on for
developing the Playwright test suite.

Alternatively, you can run the tests locally using
[gitlab-ci-local](https://github.com/firecow/gitlab-ci-local)
without any further setup required:

`gitlab-ci-local playwright`

or

`gitlab-ci-local "playwright (previous major)"`

## Committing

As much as possible, the maintainers aim to make focused, atomic commits that
can stand on their own and can fit into one of the types defined below.

Specifically, we follow the [conventional commits specification]. Our commit
messages are then used to generate release notes as well as determine the
semantic version of future releases. See [RELEASING.md](RELEASING.md) for how
this is used, and [package.json](package.json) for how this is set up.

[conventional commits specification]: https://www.conventionalcommits.org/en/v1.0.0/

### Commit types

- `chore`: Updates to internal tooling, release-related tasks, etc.
- `ci`: GitLab CI updates
- `docs`: Documentation only changes
- `feat`: A new feature
- `fix`: A bug fix
- `perf`: A code change that improves performance
- `refactor`: A code change that neither fixes a bug nor adds a feature, and
  specifically does not change any functionality
- `revert`: Reverting a commit. This type must be followed by the header of the reverted commit.
  The body of the commit must contain: `This reverts commit <hash>.`,
  where the hash is the SHA of the commit being reverted.
  See commit [c873fd7](https://git.drupalcode.org/project/ckeditor5_paste_filter/-/commit/c873fd761a636b768993d80c29722453d5ea39e0)
  for a real example, but note that this example uses an older format for the
  first line of the commit, prior to drupal.org adopting conventional commits.
- `style`: Changes that do not affect the meaning of the code (fixing code
  style lint errors, whitespace-only changes, formatting, etc)
- `test`: Testing updates

### Breaking changes

If a type is followed by `!`, that signifies a breaking change.

### Referencing Drupal.org issues

Include the issue number prefixed by a `#` directly after the commit type, for example:

```
feat: #123456789 Turbo encabulator
```

### Crediting contributors

When there is a single contributor to an issue, ensure that user is set as the
author of the git commit.

If there is more than one contributor to an issue, add them as `By`
trailers to the git commit message and list their Drupal.org username.
The Drupal.org Contribution record UI provides a commit message in this format.

#### Example commit with Drupal.org issue reference and multiple contributor trailers

```
fix: #87654321 Repair broken marzlevane

By: ExampleUsername
By: star-szr
```
