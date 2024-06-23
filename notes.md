Tracking submissions

- Table for the submissions
  - UUID
  - Status
  - user UUID (some uuid of the user who submitted)
  - created
  - updated
  - submitted
  - title
  - content --> JSON blob?

- Table for the attachments

- Table for the images


How to handle updates?

- We need to keep track of the attachments, including the description and language
- We need to keep track of the image, including the description and copyright
- Should an update be a new entry in the table --> probably not
- Shall we keep the submitted data so that it can be used to populate the form
  when updating?


What to store

1. Until call webhook call from RW post API
    --> store a blob of the submitted json payload
2. Once webhook is called --> remove data

   - When updating - retrieve latest content from RW API --> populate the form
     with that.
   - When submitting update
      --> put status to "pending" and store "blob"

Only allow to edit pending or published content.
Refused content should be blocked and data deleted?

Statuses

- pending
- published
- refused

What to do with embargoed documents? Considered pending until published?

Shall we send the status along the GET request for the webhook. Maybe that should be a POST request with the status as payload?

POST requests have the advantage of generally not being cached.

Webhook URL could be POST https://my.domain/reliefweb/submission/{uid}/{status}


---

Revisions:

Shall we make the ReliefWeb Resource entities revisionable? At least to track who submitted the content?
