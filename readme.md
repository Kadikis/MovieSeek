# Movie Seek

_Sample project by Karlis Paegle_
_OMDb API wrapper written in PHP using Laravel with Vue.js frontend using Inertia_

Project acts as FE\Cache. If we already have data stored return it and display to user, otherwise call an API and get data.

Project could be improved by fetching data in the background, since OMDb API only returns 10 item pages, but that introduces problem of showing response to user in real time. Maybe could push web socket event on success on BE.

Searches attached to the sessions so they expire after two hours of inactivity. Should add task for cleaning up expired searches from DB.

## Steps for running this project

### Prerequisites

- PHP 8.4
- Composer
- Node.js (18+ recommended)
- npm

Clone project to your local machine.

Execute steps in working directory:

1. Run command `composer setup`
2. Set value of OMDB_API_KEY in .env
3. Run project by executing `composer run dev`
