# Google Vertex AI Setup Guide for Smart Form Shield

This guide will help you set up Google Vertex AI (Gemini models) for use with Smart Form Shield WordPress plugin.

## Prerequisites

1. A Google Cloud Platform (GCP) account
2. A GCP project with billing enabled
3. Access to Google Cloud Console

## Step 1: Create or Select a GCP Project

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Click on the project selector in the top navigation bar
3. Either select an existing project or create a new one:
   - Click "New Project"
   - Enter a project name (e.g., "smart-form-shield")
   - Note your **Project ID** (you'll need this later)

## Step 2: Enable Vertex AI API

1. In the Google Cloud Console, go to **APIs & Services** → **Library**
2. Search for "Vertex AI API"
3. Click on "Vertex AI API"
4. Click the **Enable** button
5. Wait for the API to be enabled (this may take a minute)

Alternatively, using gcloud CLI:
```bash
gcloud services enable aiplatform.googleapis.com
```

## Step 3: Create an API Key

### Option A: API Key (Simpler but Limited)

**Important**: API Keys have limited support in Vertex AI. They only work if:
1. Your project has API keys enabled for Vertex AI (not all projects do)
2. The API key has proper permissions

To create an API key:

1. Go to **APIs & Services** → **Credentials**
2. Click **+ CREATE CREDENTIALS** → **API key**
3. Copy the generated API key
4. Click **Restrict Key** for security:
   - Under **Application restrictions**, select "None" (for testing)
   - Under **API restrictions**, select "Restrict key"
   - Click **Select APIs** and search for:
     - "Vertex AI API"
     - "Cloud AI Platform API" (sometimes needed)
   - Select both if available
   - Click **Save**

**Note**: If you get "invalid authentication credentials" error, your project may not support API keys for Vertex AI. In this case, you'll need to use Option B (Service Account) or Option C below.

### Option B: Alternative - Use Generative Language API Instead

If Vertex AI authentication is problematic, you can use the simpler Generative Language API:

1. Go to [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Create an API key there
3. This uses the older endpoint but supports the same models
4. No project ID or region required

**To switch back**: Contact support for instructions on reverting to Generative Language API.

### Option B: Service Account (Recommended)

The plugin fully supports service account authentication with automatic JWT signing.

1. Go to **IAM & Admin** → **Service Accounts**
2. Click **+ CREATE SERVICE ACCOUNT**
3. Enter a name (e.g., "smart-form-shield-sa")
4. Click **Create and Continue**
5. Grant these roles:
   - **Vertex AI User** (required)
   - **Service Account Token Creator** (if using domain-wide delegation)
6. Click **Continue** → **Done**
7. Click on the created service account
8. Go to **Keys** tab → **Add Key** → **Create new key**
9. Choose **JSON** format
10. Save the downloaded JSON file securely

**To use the service account in the plugin:**
1. Open the downloaded JSON file in a text editor
2. Copy the entire JSON content
3. Paste it into the API Key field in the plugin settings
4. The plugin will automatically detect it's a service account and use OAuth2

## Step 4: Configure Smart Form Shield

1. In WordPress admin, go to **Smart Form Shield** → **Settings** → **API Settings**

2. Find the **Google Vertex AI** section

3. Enter your configuration:
   - **API Key**: Paste your API key (or service account JSON)
   - **Project ID**: Enter your GCP project ID
   - **Region**: Select your preferred region (e.g., us-central1)
   - **Model**: Choose between:
     - `Gemini 2.5 Flash` - More powerful, higher cost
     - `Gemini 2.5 Flash Lite` - Lightweight, lower cost

4. Click **Test Connection** to verify setup

5. Click **Save Changes**

## Step 5: Verify Billing

Vertex AI requires billing to be enabled on your GCP project:

1. Go to **Billing** in Google Cloud Console
2. Ensure your project is linked to a billing account
3. Set up budget alerts to monitor costs

## Available Models

### Gemini 2.5 Flash
- **Model ID**: `gemini-2.5-flash`
- **Best for**: High-accuracy spam detection
- **Input tokens**: Up to 1M
- **Cost**: Pay per token (see [pricing](https://cloud.google.com/vertex-ai/generative-ai/pricing))

### Gemini 2.5 Flash Lite
- **Model ID**: `gemini-2.5-flash-lite`
- **Best for**: Cost-effective spam detection
- **Input tokens**: Up to 1M
- **Output tokens**: Up to 65K
- **Cost**: Lower than Flash (often has free tier)

## Regions

Choose a region close to your WordPress server for best performance:

- **US Central**: `us-central1` (Iowa)
- **US East**: `us-east4` (Virginia)
- **US West**: `us-west1` (Oregon), `us-west4` (Nevada)
- **Europe**: `europe-west1` (Belgium), `europe-west4` (Netherlands)
- **Asia**: `asia-northeast1` (Tokyo), `asia-southeast1` (Singapore)

## Troubleshooting

### "API not enabled" Error
- Ensure Vertex AI API is enabled in your project
- Wait a few minutes after enabling for propagation

### "Permission denied" Error
- Check that billing is enabled
- Verify API key restrictions aren't too strict
- For service accounts, ensure it has Vertex AI User role

### "Invalid project ID" Error
- Use the project ID (e.g., `my-project-123`), not the project name
- Check for typos or extra spaces

### "Model not found" Error
- Ensure you're using the exact model ID: `gemini-2.5-flash` or `gemini-2.5-flash-lite`
- Check if the model is available in your selected region

## Cost Optimization

1. **Use Gemini 2.5 Flash Lite** for most spam detection - it's cost-effective and sufficient
2. **Enable caching** in Smart Form Shield settings to avoid duplicate API calls
3. **Set up budget alerts** in GCP to monitor spending
4. **Monitor usage** in the plugin's Analytics section

## Security Best Practices

1. **Restrict API Keys**:
   - Limit to specific APIs (Vertex AI only)
   - Consider IP restrictions if your server has a static IP

2. **Use Service Accounts** (when fully supported):
   - More secure than API keys
   - Can be granted minimal required permissions

3. **Rotate Keys Regularly**:
   - Create new keys periodically
   - Update in WordPress before deleting old keys

4. **Monitor Usage**:
   - Check GCP logs for unusual activity
   - Review API usage in Google Cloud Console

## API Quotas and Limits

- **Requests per minute**: Varies by region and model
- **Token limits**: 
  - Input: Up to 1M tokens
  - Output: Model-dependent
- **Concurrent requests**: Check your project quotas

To view/increase quotas:
1. Go to **IAM & Admin** → **Quotas**
2. Filter by "Vertex AI"
3. Request increases as needed

## Additional Resources

- [Vertex AI Documentation](https://cloud.google.com/vertex-ai/docs)
- [Gemini API Overview](https://cloud.google.com/vertex-ai/generative-ai/docs/model-reference/gemini)
- [Vertex AI Pricing](https://cloud.google.com/vertex-ai/generative-ai/pricing)
- [Google Cloud Console](https://console.cloud.google.com)

## Support

For plugin-specific issues:
- Check the Smart Form Shield logs in WordPress
- Enable WP_DEBUG for detailed error messages

For Vertex AI issues:
- Check Google Cloud Console logs
- Verify API status at [Google Cloud Status](https://status.cloud.google.com)