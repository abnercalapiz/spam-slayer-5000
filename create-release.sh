#!/bin/bash

# Create GitHub Release Script for Spam Slayer 5000
# Usage: ./create-release.sh

VERSION=$(grep "Version:" spam-slayer-5000.php | head -1 | awk '{print $3}')
TAG="v$VERSION"

echo "Creating release for Spam Slayer 5000 version $VERSION"

# Check if gh CLI is installed
if ! command -v gh &> /dev/null; then
    echo "GitHub CLI (gh) is not installed. Please install it first."
    echo "Visit: https://cli.github.com/"
    exit 1
fi

# Check if authenticated
if ! gh auth status &> /dev/null; then
    echo "You are not authenticated with GitHub CLI."
    echo "Please run: gh auth login"
    exit 1
fi

# Get changelog for current version
CHANGELOG=$(awk "/### Version $VERSION/{flag=1; next} /### Version/{flag=0} flag" README.md | sed '/^$/d')

# Create release notes
cat << EOF > release-notes.md
## Release v$VERSION

### What's New

$CHANGELOG

### Installation

1. Download the \`spam-slayer-5000.zip\` file from the Assets section below
2. In WordPress Admin, go to Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate the plugin
4. Configure your AI provider API keys in Spam Slayer 5000 → Settings

### Update

If you have a previous version installed:
1. The plugin will notify you of the update in your WordPress admin
2. Click "Update Now" to automatically update to the latest version

### Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- At least one AI provider API key (OpenAI or Claude)

### Support

For support or bug reports, please visit: https://github.com/abnercalapiz/spam-slayer-5000/issues
EOF

# Create the ZIP file
echo "Creating plugin ZIP file..."
cd ..
zip -r spam-slayer-5000.zip spam-slayer-5000/ \
    -x "spam-slayer-5000/.git/*" \
    -x "spam-slayer-5000/.gitignore" \
    -x "spam-slayer-5000/create-release.sh" \
    -x "spam-slayer-5000/release-notes.md" \
    -x "spam-slayer-5000/.DS_Store" \
    -x "spam-slayer-5000/node_modules/*" \
    -x "spam-slayer-5000/vendor/*"

mv spam-slayer-5000.zip spam-slayer-5000/

cd spam-slayer-5000

# Create GitHub release with the ZIP file
echo "Creating GitHub release..."
gh release create "$TAG" \
    --title "Spam Slayer 5000 v$VERSION" \
    --notes-file release-notes.md \
    spam-slayer-5000.zip

# Clean up
rm release-notes.md
rm spam-slayer-5000.zip

echo "Release $TAG created successfully!"
echo "View it at: https://github.com/abnercalapiz/spam-slayer-5000/releases/tag/$TAG"