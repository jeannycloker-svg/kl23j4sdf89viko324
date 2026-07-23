# Releasing steps

High level: We are using [semantic versioning](https://semver.org)
and [conventional commits](https://www.conventionalcommits.org/en/v1.0.0/),
and have yarn scripts to automate some of the releasing steps.

We aim to err on the side of releasing more often,
so a goal is to make releasing consistent and relatively quick to do.

1. Run `yarn bump-check` to preview
   the type of version bump that will occur based on commits since the latest tag.

   This script will output `patch`, `minor`, or `major`,
   along with a short explanation for the version bump that was determined.
2. If the previous command outputs `minor` or `major`,
   create a new branch corresponding to this
   and make adjustments to the initial branch as needed:
   1. Create the new branch according to semantic versioning (don't switch to it yet).

      For example a new minor needs to increment the second digit,
      so if you started with 1.0.x you would create 1.1.x from the current commit:
      `git branch 1.1.x`
   2. Remove any commits that don't belong on the branch you started on.

      Specifically, any commits that add features or have breaking changes
      shouldn't end up published on the initial branch (1.0.x in this example).
      There are various ways to do this with git,
      such as interactive rebasing
      or `git reset --hard`.

      In almost all cases `git reset --hard "@{upstream}"` will be what you want here
      unless the initial branch should also get a release with some of the commits.
   3. Switch to the new branch, in this example `git switch 1.1.x`.
3. Push new commits (and branches) to the GitLab repository with `git push`.
4. Check CI status of the latest commits before tagging the new version:
   https://git.drupalcode.org/project/ckeditor5_paste_filter/-/pipelines?scope=branches

   See https://www.drupal.org/project/ckeditor5_paste_filter/issues/3509004
   for a plan to replace this testing solution.
5. Create the version bump commit and new version tag:
   1. For stable releases: `yarn bump` (semantic version will be determined
      based on commits)
   2. For unstable releases (alpha, beta, rc):
      `yarn bump-unstable [version-string]`, for example
      `yarn bump-unstable 2.0.0-rc1`
6. `git push` to push the version bump commit.
7. `git push --tags` to push the new tag.
8. `yarn changelog` to output changelog entries and a link to the diff for the
   whole release to stdout, these can be pasted into the release node for
   drupal.org in the next step.
9. Create release node: https://www.drupal.org/node/add/project-release/3349132
10. If you created a new branch in step 2,
    create a release node for the new branch:
    https://www.drupal.org/node/add/project-release/3349132
