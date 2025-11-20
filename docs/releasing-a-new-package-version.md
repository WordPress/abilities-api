# Release Process Guide: `abilities-api` Composer Package

  

This document outlines the official process for releasing a new version of the `abilities-api` composer package (repository: `https://github.com/WordPress/abilities-api`).

  

>  **Note:** The version `0.6` (and `v0.6.0`) is used as an example. Remember to replace this with the actual version you are releasing.

* * *
 
## 🚀 Phase 1: Create the Release Branch

  

1.  **Create the Branch** - Create a new release branch from the trunk branch.

```
# Replace '0.6' with your new version`

git checkout -b release/0.6 origin/trunk
```

2.  **Update .gitignore** - Include the build files in the package, edit packages/client/.gitignore and remove the following lines:

     ```
	# Build output
    build/
    build-module/
    build-types/
    ```

3.  **Build the Package** - Run the clean and build scripts:

*  `run clean && npm run build`

4.  **Commit Build Files** - Add the newly created build files and commit them.

*  `git add . && git commit -m "Add build files for the client package"`

5.  **Push the Branch** - Push the new release branch to the origin repository.

```
# Replace '0.6' with your new version

git push -u origin release/0.6
```

  

* * *

  

## 📦 Phase 2: Create the RC (Release Candidate)

  

1. Open [https://github.com/WordPress/abilities-api/releases/new](https://github.com/WordPress/abilities-api/releases/new).

2.  **Configure the RC Release** by filling out the form with the following details:

*  **Create a new tag:** Use the format `vX.Y.Z-rc` (e.g., `v0.6.0-rc`).

*  **Set the target:** Select the `release/0.6` branch you just pushed.

*  **Set the title:** Use the same value as the tag (e.g., `v0.6.0-rc`).

*  **Generate release notes:** Click the "Generate release notes" button and edit the generated notes as needed.

3.  **Set Release Options**

*  **Check** ✅ `Set as a pre-release`.

*  **Uncheck** ⬜️ `Set as the latest release`.

4.  **Publish and Verify**

* Click "Publish release".

* Wait a few minutes, then verify the new RC version appears on [Packagist](https://packagist.org/packages/wordpress/abilities-api).

  

* * *  

## 🧪 Phase 3: Announce for Testing

  

Post an announcement in the #core slack channel saying a new RC version is ready for testing, and ask users/developers to report any issues found in that version.

  

* * *  

## ✅ Phase 4: Publish the Final Release

  

**After testing is complete** promote the RC to the final release.

  

1.  **Find the RC Release** - Go to the GitHub Releases page, find the pre-release you just created (e.g., v0.6.0-rc), and click Edit.

2.  **Update Tag and Title**

*  **Create a new tag:** Change the tag to the final version (e.g., `v0.6.0`).

*  **Target:** Ensure the target remains your release branch (e.g., `release/0.6`).

*  **Title:** Remove the `-rc` suffix from the title (e.g., `v0.6.0`).

3.  **Update Release Options**

*  **Uncheck** ⬜️ `Set as a pre-release`.

*  **Check** ✅ `Set as the latest release`.

4.  **Publish the Final Release** - Click "Update release". This will make v0.6.0 the new official version.