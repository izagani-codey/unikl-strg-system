# 🔧 Authorization Debugging Steps

## 🚨 **Current Issue: 403 Unauthorized**

### **✅ Fixes Applied So Far**

#### **1. Created AuthServiceProvider**
- **File**: `app/Providers/AuthServiceProvider.php`
- **Purpose**: Register RequestPolicy for authorization
- **Mapping**: `Request::class => RequestPolicy::class`
- **Alias**: `'GrantRequest' => RequestPolicy::class`

#### **2. Updated RequestPolicy**
- **Added Dean Role**: All policy methods now include `'dean'` role
- **Updated Methods**: `view()`, `viewAny()`, `changeStatus()`, `addComment()`
- **Dean Permissions**: Can view all requests, action dean approval requests

#### **3. Registered Provider**
- **File**: `bootstrap/app.php`
- **Added**: `App\Providers\AuthServiceProvider::class`
- **Purpose**: Load authorization policies

#### **4. Created Test Route**
- **URL**: `/test-auth` (development only)
- **Purpose**: Debug authorization issues
- **Returns**: User role, policy results, gate checks

## 🔍 **Debugging Steps**

### **Step 1: Test Authorization**
Visit: `http://my-app.test/test-auth`

**Expected Response:**
```json
{
    "user_role": "dean",
    "request_id": 1,
    "can_view_any": true,
    "can_view": true,
    "policy_result": {
        "viewAny_result": true,
        "view_result": true
    }
}
```

### **Step 2: Check Policy Registration**
```bash
php artisan tinker
>>> app('auth')->getPolicyFor(Request::class);
>>> app('auth')->getPolicyFor('GrantRequest');
```

### **Step 3: Verify User Role**
```bash
php artisan tinker
>>> Auth::loginUsingId(1); // Login as dean
>>> Auth::user()->role;
>>> Auth::user()->isDean();
```

### **Step 4: Test Policy Directly**
```bash
php artisan tinker
>>> $user = Auth::user();
>>> $request = Request::first();
>>> $policy = new \App\Policies\RequestPolicy();
>>> $policy->viewAny($user);
>>> $policy->view($user, $request);
```

## 🎯 **Common Issues & Solutions**

### **Issue 1: Policy Not Registered**
**Symptom**: 403 errors for all users
**Fix**: Ensure AuthServiceProvider is registered in `bootstrap/app.php`

### **Issue 2: Role Mismatch**
**Symptom**: 403 errors for specific roles
**Fix**: Check user role in database vs policy expectations

### **Issue 3: Cache Issues**
**Symptom**: Changes not taking effect
**Fix**: Clear all caches:
```bash
php artisan optimize:clear
php artisan optimize
```

### **Issue 4: Model Alias**
**Symptom**: Policy not applying to controller methods
**Fix**: Ensure `'GrantRequest' => RequestPolicy::class` mapping exists

## 🚀 **Testing Checklist**

### **✅ Test All Roles**
1. **Login as Admission**: Check own requests only
2. **Login as Staff 1**: Check all requests
3. **Login as Staff 2**: Check all requests + override
4. **Login as Dean**: Check all requests + dean approval

### **✅ Test Endpoints**
1. **Dashboard**: `/dashboard` - Should load per role
2. **Request List**: `/requests` - Should show appropriate requests
3. **Request Detail**: `/requests/{id}` - Should load if authorized
4. **Test Auth**: `/test-auth` - Should return true for dean

### **✅ Test Policy Methods**
1. **viewAny()**: Can user view any requests?
2. **view()**: Can user view specific request?
3. **changeStatus()**: Can user change request status?
4. **addComment()**: Can user add comments?

## 📋 **Expected Results**

### **Dean User Should Be Able To:**
- ✅ View dashboard with pending approvals
- ✅ View all requests in system
- ✅ Access individual request details
- ✅ Approve/reject requests in dean approval status
- ✅ Add comments to requests they can action

### **All Users Should Be Able To:**
- ✅ Access their role-specific dashboard
- ✅ View requests according to their permissions
- ✅ Perform actions appropriate to their role
- ✅ Navigate without 403 errors

## 🎯 **Next Steps**

If still getting 403 errors:

1. **Visit `/test-auth`** to check authorization status
2. **Check Laravel logs** for specific error messages
3. **Verify user role** in database
4. **Clear all caches** and restart
5. **Check policy registration** in tinker

The authorization system should now be working correctly with the dean role fully integrated.
