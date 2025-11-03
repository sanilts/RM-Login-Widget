# Elementor Widgets - Quick Reference Card

## 🎯 Widget Overview

| Widget | Icon | Purpose | Key Features |
|--------|------|---------|--------------|
| **Login Form** | 🔐 | User authentication | Role-based redirect, WPML, styling |
| **Survey Listing** | 📊 | Display surveys | Grid/list/cards, filtering, pagination |
| **Survey Accordion** | 📑 | Collapsible surveys | Accordion/tabs, grouping, animations |
| **Profile Picture** | 📸 | Upload avatar | Drag & drop, cropping, FluentCRM sync |

---

## 🔐 Login Widget

### Quick Setup
```
1. Drag "Login Form" to canvas
2. Set redirect type
3. Style form colors
4. Publish
```

### Common Settings
| Setting | Default | Options |
|---------|---------|---------|
| Redirect | Dashboard | Dashboard, Custom URL, Same Page, Role-based |
| Remember Me | Yes | Yes/No |
| Lost Password | Yes | Yes/No |

### Role-Based Redirects
```php
Administrator → /admin-dashboard/
Subscriber   → /my-dashboard/
Custom Role  → /custom-url/
```

---

## 📊 Survey Listing Widget

### Quick Setup
```
1. Drag "Survey Listing" to canvas
2. Choose layout (grid/list/cards)
3. Set columns (1-6)
4. Configure filters
5. Style cards
```

### Layout Options
```
Grid:  ▢ ▢ ▢  (Responsive columns)
List:  ▬ Full width rows
Cards: ▢ With shadows & hover
```

### Display Controls
| Element | Toggle | Description |
|---------|--------|-------------|
| Image | ✓ | Featured image |
| Excerpt | ✓ | Short description |
| Questions | ✓ | Question count |
| Duration | ✓ | Time estimate |
| Earnings | ✓ | Reward amount |
| Status | ✓ | Completed/Active badge |

### Filtering
```php
Category:  Select multiple categories
Status:    Show/hide completed
Order By:  Date, Title, Modified
Per Page:  1-50 surveys
```

---

## 📑 Survey Accordion Widget

### Quick Setup
```
1. Drag "Survey Accordion" to canvas
2. Choose accordion or tabs
3. Set grouping (category/status)
4. Configure animations
5. Style headers
```

### Display Types
```
Accordion:  ▼ Click to expand/collapse
Tabs:       [Tab1] [Tab2] [Tab3]
```

### Grouping Options
```
By Category: Group surveys by category
By Status:   Active | Completed | Expired
No Group:    Single list
```

### Animation
```
Duration: 0-1000ms
Easing:   Ease, Linear, Ease-in-out
```

---

## 📸 Profile Picture Widget

### Quick Setup
```
1. Drag "Profile Picture" to canvas
2. Set max file size
3. Enable/disable cropping
4. Configure FluentCRM sync
5. Style upload area
```

### File Settings
```
Max Size:  1-10 MB (default: 2MB)
Formats:   JPG, JPEG, PNG, GIF
Cropping:  Yes/No
```

### Upload Methods
```
1. Click to upload
2. Drag & drop
3. Paste from clipboard
```

### FluentCRM Integration
```
✓ Auto-sync to contact avatar
✓ Update on change
✓ Remove on delete
```

---

## 🎨 Common Style Controls

### All Widgets Support

**Colors:**
- Background color
- Text color
- Hover states
- Border color

**Typography:**
- Font family
- Font size
- Font weight
- Line height
- Letter spacing

**Spacing:**
- Padding (all sides)
- Margin (all sides)
- Custom spacing units (px, em, %)

**Border:**
- Border width
- Border style
- Border radius
- Box shadow

**Responsive:**
- Desktop settings
- Tablet settings
- Mobile settings

---

## ⚙️ Widget Settings Location

### Elementor Editor
```
Left Panel → Widgets → RM Panel Widgets
│
├── 🔐 Login Form
├── 📊 Survey Listing
├── 📑 Survey Accordion
└── 📸 Profile Picture
```

### Widget Controls
```
Content Tab:   Main settings & options
Style Tab:     Visual styling
Advanced Tab:  CSS, animations, custom
```

---

## 🔧 Quick Troubleshooting

### Widget Not Showing
```bash
1. Check Elementor is active
2. Verify plugin active
3. Clear Elementor cache
4. Regenerate CSS
```

### Styles Not Applied
```bash
1. Clear browser cache
2. Clear WordPress cache
3. Regenerate Elementor CSS
4. Check CSS conflicts
```

### AJAX Not Working
```javascript
// Check browser console
Press F12 → Console tab
Look for errors
```

### Image Upload Fails
```bash
1. Check file size limit
2. Verify file format
3. Check PHP upload_max_filesize
4. Check file permissions
```

---

## 📱 Responsive Design

### Breakpoints
```
Desktop:  > 1024px
Tablet:   768px - 1024px
Mobile:   < 768px
```

### Responsive Controls
```
Columns:       Desktop [3] Tablet [2] Mobile [1]
Font Size:     Desktop [18px] Tablet [16px] Mobile [14px]
Padding:       Desktop [30px] Tablet [20px] Mobile [15px]
```

---

## 🌍 WPML Support

### Translatable Elements
```
✓ Form titles
✓ Button text
✓ Error messages
✓ Placeholder text
✓ Help text
```

### Setup
```
1. Enable WPML in settings
2. Register strings
3. Translate via WPML
4. Test in each language
```

---

## 🚀 Performance Tips

### Optimization
```
✓ Conditional loading (assets only when needed)
✓ Minified CSS/JS
✓ Lazy load images
✓ Cache queries
✓ Optimize images
```

### Best Practices
```
✓ Use appropriate layout
✓ Limit posts per page
✓ Enable pagination
✓ Optimize images before upload
✓ Use CDN for assets
```

---

## 📊 Usage Examples

### Login Widget
```php
// Redirect administrators to admin panel
Administrator → /wp-admin/
// Redirect subscribers to dashboard
Subscriber → /my-dashboard/
```

### Survey Listing
```php
// Show 6 surveys in 3 columns
Posts: 6
Columns: 3
Layout: Grid

// Filter by category
Categories: [Research, Feedback]
```

### Survey Accordion
```php
// Group by category, accordion style
Type: Accordion
Group: Category
Animation: 300ms
```

### Profile Picture
```php
// Max 2MB, JPG/PNG only, with cropping
Max Size: 2MB
Formats: JPG, PNG
Cropping: Enabled
```

---

## 🎓 Learning Resources

### Video Tutorials
```
Coming Soon:
├── Widget Overview (5 min)
├── Login Widget Tutorial (10 min)
├── Survey Listing Guide (15 min)
└── Advanced Customization (20 min)
```

### Code Examples
```
GitHub: github.com/researchandmetric/rm-panel-examples
Docs: docs.researchandmetric.com
```

---

## ✅ Pre-Launch Checklist

### Before Publishing
- [ ] Test on desktop
- [ ] Test on tablet
- [ ] Test on mobile
- [ ] Check all browsers
- [ ] Test logged in/out
- [ ] Verify redirects
- [ ] Check AJAX functions
- [ ] Test with WPML
- [ ] Clear all caches
- [ ] Preview before publish

---

## 🆘 Quick Help

### Common Questions

**Q: Can I use multiple login widgets?**  
A: Yes, but redirect settings may conflict

**Q: How many surveys can I display?**  
A: Up to 50 per page (for performance)

**Q: Does it work with any theme?**  
A: Yes, fully compatible with Elementor themes

**Q: Can I customize the CSS?**  
A: Yes, via Advanced tab or custom CSS

**Q: Is it mobile responsive?**  
A: Yes, fully responsive with controls

---

## 📞 Support

**Need Help?**
- 📧 support@researchandmetric.com
- 📖 Full Docs: ELEMENTOR-WIDGETS-DOCUMENTATION.md
- 🌐 Website: https://researchandmetric.com

---

## 🔖 Quick Commands

### Clear Caches
```bash
# Elementor Cache
Tools → Regenerate CSS & Data

# WordPress Cache
Dashboard → Clear Cache (if plugin installed)

# Browser Cache
Ctrl + Shift + R (Windows)
Cmd + Shift + R (Mac)
```

### Debug Mode
```php
// Add to wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

---

**Version:** 1.2.0  
**Print this card for quick reference!** 📋

**Tip:** Bookmark this page for instant access during development! 🔖
