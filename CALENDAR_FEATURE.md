# Calendar Feature for Exam Scheduling

This document describes the calendar feature implemented for scheduling exams in the regulacao-list application.

## Overview

The calendar feature allows users to:
- Schedule exams with specific dates and times
- View scheduled exam dates in entry listings
- Reschedule existing exams
- Use an intuitive calendar interface for date selection

## Components Implemented

### 1. Calendar Component (`resources/js/components/ui/calendar/Calendar.vue`)

A custom calendar component built with Vue 3 that provides:
- Month navigation with previous/next buttons
- Date selection with visual feedback
- Minimum and maximum date constraints
- Support for Portuguese locale
- Responsive design with Tailwind CSS

**Features:**
- Highlights today's date
- Shows selected date with primary color
- Grays out dates outside current month
- Disables dates outside allowed range

### 2. DatePicker Component (`resources/js/components/ui/date-picker/DatePicker.vue`)

A dropdown date picker that combines a button trigger with the calendar component:
- Shows formatted date in Portuguese (DD/MM/YYYY)
- Opens calendar in a modal dialog
- Includes clear button functionality
- Placeholder text support

### 3. ExamScheduleModal Component (`resources/js/components/ExamScheduleModal.vue`)

A comprehensive modal for scheduling exams with:
- Date selection using the DatePicker
- Time selection with 30-minute intervals (7:00 AM to 6:00 PM)
- Optional reason/notes field
- Preview of selected date and time
- Current scheduling display (if exam already scheduled)
- Integration with backend API

## API Integration

### Backend Updates

1. **Controller Updates** (`app/Http/Controllers/EntryController.php`):
   - Enhanced `scheduleExam` method to accept reason parameter
   - Returns updated entry data after scheduling

2. **Model Updates**:
   - Entry model already had `scheduleExam` method
   - Status transition system supports scheduled dates in metadata

3. **API Endpoints**:
   - `PUT /api/entries/{id}/schedule-exam` - Schedule or reschedule exam
   - Accepts `exam_scheduled_date` and optional `reason`

### Frontend Integration

Updated the following pages to include the ExamScheduleModal:
- `resources/js/pages/ActiveEntries.vue`
- `resources/js/pages/ScheduledEntries.vue`

The modal is added alongside the StatusTransitionDropdown component.

## Usage

### For Users

1. **Scheduling an Exam:**
   - Navigate to Active Entries or Scheduled Entries
   - Click the "Agendar Exame" button for an entry
   - Select a date using the calendar
   - Choose a time from the dropdown
   - Add optional notes
   - Click "Agendar" to confirm

2. **Rescheduling an Exam:**
   - For entries with existing scheduled dates, the button shows "Reagendar Exame"
   - The modal pre-fills with current scheduled date and time
   - Change date/time as needed and save

3. **Viewing Scheduled Dates:**
   - Scheduled exam dates appear in the entry listings
   - Formatted in Portuguese locale (e.g., "15/03/2024 09:30")

### For Developers

```javascript
// Import the component
import ExamScheduleModal from '@/components/ExamScheduleModal.vue';

// Use in template
<ExamScheduleModal
    :entry="entry"
    @exam-scheduled="onExamScheduled"
    @error="onError"
/>

// Handle events
function onExamScheduled(updatedEntry) {
    // Entry has been updated with new scheduled date
    // Refresh data or update local state
}

function onError(errorMessage) {
    // Handle scheduling errors
    console.error(errorMessage);
}
```

## Technical Details

### Date Handling

- All dates are handled in ISO format (YYYY-MM-DD) for API communication
- Times are in 24-hour format (HH:MM)
- Combined datetime is sent as ISO string (YYYY-MM-DDTHH:MM:SS)
- Frontend displays dates in Portuguese locale

### Validation

- Minimum date: Today
- Maximum date: 6 months from now
- Time slots: Every 30 minutes from 07:00 to 18:00
- Required fields: Date and Time

### Status Integration

When an exam is scheduled:
1. Entry transitions to "exam_scheduled" status
2. Scheduled date is stored in status transition metadata
3. Entry model provides `scheduled_exam_date` accessor
4. UI reflects the new status and date

## File Structure

```
resources/js/
├── components/
│   ├── ExamScheduleModal.vue           # Main scheduling modal
│   └── ui/
│       ├── calendar/
│       │   ├── Calendar.vue            # Calendar component
│       │   └── index.ts               # Calendar exports
│       └── date-picker/
│           ├── DatePicker.vue         # Date picker component
│           └── index.ts               # DatePicker exports
└── pages/
    ├── ActiveEntries.vue              # Updated with scheduling
    └── ScheduledEntries.vue           # Updated with scheduling
```

## Dependencies

- Vue 3
- Reka UI (for base components)
- Lucide Vue Next (for icons)
- Tailwind CSS (for styling)
- Class Variance Authority (for component variants)

## Future Enhancements

Potential improvements for the calendar feature:

1. **Multiple Time Zones Support**
2. **Recurring Appointments**
3. **Calendar View** - Month/week view of all scheduled exams
4. **Email Notifications** - Send reminders before scheduled exams
5. **Conflict Detection** - Prevent double-booking of time slots
6. **Bulk Scheduling** - Schedule multiple exams at once
7. **Holiday Integration** - Disable holidays/weekends
8. **Custom Time Intervals** - Allow 15-minute or 1-hour slots

## Testing

The calendar components can be tested using the `CalendarTest.vue` component:

```vue
<CalendarTest />
```

This component demonstrates both the Calendar and DatePicker components with debug information.

## Troubleshooting

### Common Issues

1. **Calendar not showing:**
   - Check that all UI components are properly imported
   - Verify Tailwind CSS classes are available

2. **Date not saving:**
   - Check browser console for API errors
   - Verify date format is correct (YYYY-MM-DD)

3. **Styling issues:**
   - Ensure Tailwind CSS is properly configured
   - Check for conflicting CSS classes

### Development

To add the calendar to additional pages:

1. Import the component:
   ```javascript
   import ExamScheduleModal from '@/components/ExamScheduleModal.vue';
   ```

2. Add to template:
   ```vue
   <ExamScheduleModal
       :entry="entry"
       @exam-scheduled="handleScheduled"
       @error="handleError"
   />
   ```

3. Handle events in your component's script section.