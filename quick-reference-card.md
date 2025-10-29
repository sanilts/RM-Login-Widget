# Profile Picture Widget - Quick Reference Card

## 🎯 Essential Code Snippets

### Get User Profile Picture (PHP)
```php
// Basic usage
$url = RM_Profile_Picture_Handler::get_user_profile_picture(get_current_user_id());

// Different sizes
$thumb = RM_Profile_Picture_Handler::get_user_profile_picture($user_id, 'thumbnail');
$medium = RM_Profile_Picture_Handler::get_user_profile_picture($user_id, 'medium');
$large = RM_Profile_Picture_Handler::get_user_profile_picture($user_id, 'large');
$full = RM_Profile_Picture_Handler::get_user_profile_picture($user_id, 'full');

// In template
echo '<img src="' . esc_url($url) . '" alt="Profile Picture">';
```

### Check if User Has Custom Picture
```php
$attachment_id = get_user_meta($user_id, 'rm_profile_picture', true);
if ($attachment_id) {
    echo "User has custom profile picture";
} else {
    echo "Using default Gravatar";
}
```

### Frontend Script Enqueue (CRITICAL)
```php
// In enqueue_frontend_scripts() method
if (is_user_logged_in()) {
    // CSS First
    wp_enqueue_style('rm-profile-picture-widget', 
        RM_PANEL_EXT_PLUGIN_URL . 'assets/css/profile-picture-widget.css', 
        [], RM_PANEL_EXT_VERSION);
    
    // JavaScript with jQuery
    wp_enqueue_script('rm-profile-picture-widget', 
        RM_PANEL_EXT_PLUGIN_URL . 'assets/js/profile-picture-widget.js', 
        ['jquery'], RM_PANEL_EXT_VERSION, true);
    
    // Localize (AFTER enqueue)
    wp_localize_script('rm-profile-picture-widget', 'rmProfilePicture', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('rm_profile_picture_nonce')
    ]);
}
```

---

## 🔧 Debug Commands

### Browser Console
```javascript
// Check if loaded
typeof rmProfilePicture           // Should show "object"
console.log(rmProfilePicture);    // Shows ajax_url and nonce

// Open modal manually
jQuery('#rm-profile-picture-modal').addClass('active');

// Update image manually
jQuery('.rm-profile-picture-image').attr('src', 'NEW_URL');

// Get user ID
jQuery('.rm-profile-picture-image').data('user-id');
```

### PHP/MySQL
```php
// Get attachment ID
$id = get_user_meta($user_id, 'rm_profile_picture', true);

// Get upload history
$history = get_user_meta($user_id, 'rm_profile_picture_history', true);

// Clear profile picture
delete_user_meta($user_id, 'rm_profile_picture');
```

```sql
-- Count users with pictures
SELECT COUNT(*) FROM wp_usermeta WHERE meta_key = 'rm_profile_picture';

-- Find all profile pictures
SELECT user_id, meta_value FROM wp_usermeta WHERE meta_key = 'rm_profile_picture';
```

---

## ⚠️ Common Issues - Quick Fixes

### Widget Not Appearing
```javascript
// Check if user logged in (required)
// Check if scripts loaded: typeof rmProfilePicture
// View console for errors (F12)
```

### Modal Not Opening
```javascript
// Check jQuery loaded: typeof jQuery
// Manually trigger: jQuery('#rm-profile-picture-modal').addClass('active');
```

### Upload Fails
```php
// Check nonce: console.log(rmProfilePicture.nonce);
// Check file size: Must be < 5MB
// Check file type: JPG, PNG, GIF only
// Check PHP limits: ini_get('upload_max_filesize');
```

### Image Not Updating
```javascript
// Check response: Network tab in browser
// Clear cache: Add ?t=timestamp to URL
// Check element exists: jQuery('.rm-profile-picture-image').length
```

---

## 📋 Pre-Flight Checklist

### Before Going Live
- [ ] Enqueue code added correctly
- [ ] jQuery dependency included
- [ ] wp_localize_script after wp_enqueue_script
- [ ] Nonce matches: 'rm_profile_picture_nonce'
- [ ] Only loads for logged-in users
- [ ] File existence checks in place
- [ ] WP_DEBUG tested
- [ ] Upload limits checked (5MB)
- [ ] File types validated (JPG/PNG/GIF)
- [ ] Tested on mobile
- [ ] Tested in multiple browsers
- [ ] Modal opens/closes properly
- [ ] Drag & drop works
- [ ] Image updates after upload
- [ ] Old images delete properly
- [ ] Gravatar fallback works

---

## 🔐 Security Checklist

- [ ] Nonce verification on all AJAX
- [ ] User ID verified on upload
- [ ] File type validated (MIME)
- [ ] File size validated (5MB)
- [ ] User must be logged in
- [ ] Inputs sanitized
- [ ] SQL prepared statements
- [ ] IP address logged
- [ ] Upload history limited (5)
- [ ] Unused attachments deleted

---

## 📊 File Specifications

### Allowed File Types
- JPEG (image/jpeg)
- JPG (image/jpg)
- PNG (image/png)
- GIF (image/gif)

### File Size Limit
- Maximum: 5MB (5 * 1024 * 1024 bytes)
- Client-side validation
- Server-side validation (double-check)

### Image Sizes Generated
- Thumbnail: 150x150px
- Medium: 300x300px
- Large: 1024x1024px
- Full: Original size

### User Meta Keys
- `rm_profile_picture` - Current attachment ID
- `rm_profile_picture_history` - Last 5 uploads (array)

---

## 🎨 Key CSS Classes

### Structure
```css
.rm-profile-picture-container          /* Outer wrapper */
.rm-profile-picture-image-wrapper      /* Clickable area */
.rm-profile-picture-image              /* The image */
.rm-profile-picture-overlay            /* Hover effect */
```

### Modal
```css
.rm-profile-picture-modal              /* Modal backdrop */
.rm-modal-content                      /* Modal box */
.rm-upload-area                        /* Drop zone */
.rm-preview-area                       /* Preview section */
```

### States
```css
.rm-upload-area.dragover               /* File dragged over */
.rm-btn.loading                        /* Button loading */
.rm-message.success                    /* Success message */
.rm-message.error                      /* Error message */
```

---

## 🔌 AJAX Endpoints

### Upload Picture
```javascript
action: 'rm_upload_profile_picture'
nonce: rmProfilePicture.nonce
user_id: Current user ID
profile_picture: File object
```

### Get Picture
```javascript
action: 'rm_get_profile_picture'
nonce: rmProfilePicture.nonce
```

### Delete Picture
```javascript
action: 'rm_delete_profile_picture'
nonce: rmProfilePicture.nonce
```

---

## 🚀 Performance Tips

✅ Only loads for logged-in users  
✅ Scripts in footer (non-blocking)  
✅ 5-minute cache on attachments  
✅ Old images auto-deleted  
✅ Preview before upload (no server)  
✅ Conditional enqueue  
✅ Minified assets  

---

## 📞 When Things Go Wrong

1. **Check browser console** (F12)
2. **Enable WP_DEBUG** in wp-config.php
3. **Check debug.log** in wp-content
4. **Verify file paths** exist
5. **Test jQuery** loaded
6. **Check nonce** valid
7. **Verify user** logged in
8. **Test file** size/type
9. **Check PHP** upload limits
10. **View Network** tab for AJAX

---

## 📚 Documentation Files

- `profile-picture-widget-additions.md` - Full documentation (35K words)
- `quick-integration-guide.md` - Integration steps (16 steps)
- `summary.md` - Overview and usage (this document)

---

## 🎯 Critical Constants

```php
RM_PANEL_EXT_PLUGIN_DIR      // Plugin directory path
RM_PANEL_EXT_PLUGIN_URL      // Plugin URL
RM_PANEL_EXT_VERSION         // Version for cache busting
```

---

## ⚡ One-Liner Solutions

**Widget not showing?**
```javascript
is_user_logged_in() && typeof rmProfilePicture !== 'undefined'
```

**Modal not opening?**
```javascript
jQuery('#rm-profile-picture-modal').addClass('active');
```

**Upload failing?**
```php
wp_verify_nonce($_POST['nonce'], 'rm_profile_picture_nonce')
```

**Image not updating?**
```javascript
jQuery('.rm-profile-picture-image').attr('src', response.data.url + '?t=' + Date.now());
```

---

**Version:** 1.0  
**Created:** October 29, 2025  
**Print this card and keep it handy! 📌**
