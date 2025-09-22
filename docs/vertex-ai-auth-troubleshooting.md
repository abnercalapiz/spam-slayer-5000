# Vertex AI Authentication Troubleshooting Guide

## Error: "Request had invalid authentication credentials"

This error occurs when the authentication token is invalid or the service account doesn't have proper permissions.

### Quick Checklist

1. **Enable Vertex AI API**
   - Go to [APIs & Services > Library](https://console.cloud.google.com/apis/library)
   - Search for "Vertex AI API"
   - Click Enable

2. **Check Service Account Permissions**
   - Go to [IAM & Admin > Service Accounts](https://console.cloud.google.com/iam-admin/serviceaccounts)
   - Find your service account
   - Click on it and verify it has the **Vertex AI User** role

3. **Verify Billing is Active**
   - Go to [Billing](https://console.cloud.google.com/billing)
   - Ensure your project has an active billing account

4. **Test with gcloud CLI** (if available)
   ```bash
   # Set project
   gcloud config set project YOUR_PROJECT_ID
   
   # Test authentication
   gcloud auth application-default login
   
   # Test Vertex AI access
   gcloud ai models list --region=us-central1
   ```

### Common Issues and Solutions

#### 1. Service Account Missing Permissions

**Solution**: Add required roles
```bash
gcloud projects add-iam-policy-binding YOUR_PROJECT_ID \
    --member="serviceAccount:YOUR_SERVICE_ACCOUNT_EMAIL" \
    --role="roles/aiplatform.user"
```

#### 2. API Not Enabled

**Solution**: Enable via command line
```bash
gcloud services enable aiplatform.googleapis.com
```

#### 3. Wrong Project ID

**Solution**: Verify project ID matches exactly
- Project ID is NOT the project name
- Example: `my-project-123456` not "My Project"

#### 4. Service Account Key Issues

**Solution**: Create a new key
1. Go to Service Accounts
2. Click the three dots menu → Manage keys
3. Add Key → Create new key → JSON
4. Use the newly downloaded JSON

### Enable Debug Logging

To see detailed error messages:

1. Add to `wp-config.php`:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   define( 'WP_DEBUG_DISPLAY', false );
   ```

2. Check logs at: `wp-content/debug.log`

### Alternative: Use Google AI Studio

If Vertex AI continues to have issues, consider using Google AI Studio:

1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Create an API key
3. Update the plugin code to use Generative Language API instead

### Need More Help?

1. Check Google Cloud logs:
   - Go to [Logs Explorer](https://console.cloud.google.com/logs)
   - Filter by "Vertex AI"

2. Verify service account JSON structure:
   ```json
   {
     "type": "service_account",
     "project_id": "your-project-id",
     "private_key_id": "...",
     "private_key": "-----BEGIN PRIVATE KEY-----\n...",
     "client_email": "your-sa@your-project.iam.gserviceaccount.com",
     "client_id": "...",
     "auth_uri": "https://accounts.google.com/o/oauth2/auth",
     "token_uri": "https://oauth2.googleapis.com/token",
     "auth_provider_x509_cert_url": "...",
     "client_x509_cert_url": "..."
   }
   ```

3. Test with minimal permissions first, then add more as needed.