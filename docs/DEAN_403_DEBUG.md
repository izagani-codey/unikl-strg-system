# 🔧 Dean 403 Authorization Debug

## 🚨 **Problem**
Dean getting "403 Unauthorized" when trying to approve requests.

## 🔍 **Debugging Steps Applied**

### **1. Temporarily Disabled Authorization**
**RequestController.php updateStatus method:**
```php
public function updateStatus(UpdateStatusRequest $request, $id)
{
    $grantRequest = GrantRequest::findOrFail($id);
    // $this->authorize('changeStatus', $grantRequest); // TEMPORARILY DISABLED
    
    $newStatus = RequestStatus::from($request->input('status_id'));
    // ... rest of method
}
```

### **2. Policy Check Results**
**RequestPolicy.php changeStatus method:**
```php
public function changeStatus(User $user, Request $request): Response|bool
{
    if (!in_array($user->role, ['staff1', 'staff2', 'dean'])) {
        return Response::deny('Only staff members and dean can update request status.');
    }

    $currentStatus = RequestStatus::from($request->status_id);
    
    // Dean can approve/reject requests that need dean approval
    if ($user->role === 'dean' && !$currentStatus->canBeActionedByDean()) {
        return Response::deny('This request cannot be actioned by Dean at this stage.');
    }
    
    return true;
}
```

**RequestStatus.php dean method:**
```php
public function canBeActionedByDean(): bool
{
    return $this === self::PENDING_DEAN_APPROVAL;
}
```

## 🎯 **Likely Root Cause**

### **Request Status Issue**
The dean can only action requests with status `PENDING_DEAN_APPROVAL` (status 3). If the request is in any other status, the dean will get 403.

**Possible Issues:**
1. **Wrong Status**: Request might be in `PENDING_RECOMMENDATION` (status 2) instead of `PENDING_DEAN_APPROVAL` (status 3)
2. **Status Mismatch**: Staff 2 might not have properly sent to dean
3. **Workflow Issue**: Request might be stuck in wrong status

## 📋 **Test This Now**

### **Step 1: Try Dean Approval**
1. **Login as Dean**: Use dev switcher
2. **Access Request**: Go to request details
3. **Click Approve**: Try the approve button
4. **Check Result**: 
   - **If works**: Authorization was the issue
   - **If still 403**: Request status is wrong

### **Step 2: Check Request Status**
If dean approval works without authorization, check:
1. **Current Status**: What status is the request actually in?
2. **Expected Status**: Should be `PENDING_DEAN_APPROVAL` (3)
3. **Workflow**: Did Staff 2 properly send to dean?

## 🔧 **Potential Fixes**

### **Fix 1: Correct Request Status**
If request is in wrong status, update it:
```sql
UPDATE requests SET status_id = 3 WHERE id = [request_id];
```

### **Fix 2: Update Staff 2 Workflow**
Ensure Staff 2 properly sends requests to dean:
- Check Staff 2 "Send to Dean" button
- Verify status changes from 2 to 3
- Test complete workflow

### **Fix 3: Adjust Dean Policy**
If dean should action more statuses, update policy:
```php
public function canBeActionedByDean(): bool
{
    return in_array($this, [self::PENDING_DEAN_APPROVAL, self::PENDING_DEAN_VERIFICATION]);
}
```

## 🚀 **Next Steps**

1. **Test Current Fix**: Try dean approval with authorization disabled
2. **Identify Status**: Check what status the request is actually in
3. **Fix Root Cause**: Either update request status or fix workflow
4. **Re-enable Authorization**: Put authorization back once fixed

## 📊 **Expected Workflow**

**Correct Status Flow:**
1. **Admission**: Creates request (status 1)
2. **Staff 1**: Verifies (status 2)
3. **Staff 2**: Recommends (status 3)
4. **Dean**: Approves/Rejects (status 8/9)

**Dean Can Only Action:**
- Status 3: `PENDING_DEAN_APPROVAL` ✅
- All other statuses: 403 ❌

## 🎯 **Immediate Action**

**Try the dean approve button now. If it works, the issue is request status. If it still fails, there's another problem.**
