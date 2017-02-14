<template>
  <div id="app">
    <section class="your-info">
      <div class="form-group">
        <label for="eventName">Naam van het evenement</label>
        <input type="text" v-model="eventName" id="eventName" class="form-control">
      </div>
      <div class="form-group">
        <label for="eventLocation">Locatie</label>
        <input type="text" v-model="eventLocation" id="eventLocation" class="form-control">
      </div>
      <div class="form-group">
        <label for="eventDescription">Omschrijving van het evenement</label>
        <textarea v-model="eventDescription" id="eventDescription" class="form-control"></textarea>
      </div>
      <calendar :existingDates="[]" @newDateArray="updateDate($event)"></calendar>
    </section>

    <section class="your-info">
      <div class="form-group">
        <label for="userName">Jouw naam</label>
        <input type="text" v-model="userName" id="userName" class="form-control">
      </div>
      <div class="form-group">
        <label for="userEmail">Jouw e-mailadres</label>
        <input type="text" v-model="userEmail" id="userEmail" class="form-control">
      </div>
    </section>

    <h1>Voeg mensen toe die je wilt uitnodigen</h1>
    <section class="invites">
      <ul>
        <li class="userListItem" v-for="(invite, index) in invites">
          <span class="number">{{index + 1}}</span>
          <div class="user">
            <span class="name">
              <label>Naam</label>
              <input type="text" @keyup="allInvitesValid()" v-model="invite.name" class="form-control" name="name">
            </span>
            <span class="email">
              <label>E-mail</label>
              <input type="email" @keyup="allInvitesValid()" v-model="invite.email" class="form-control" name="email">
            </span>
          </div>
          <button class="btn btn-danger removeUser" @click="removeInvite(invite)">X</button>
        </li>
      </ul>
      <a href="#" @click="addNewInvite()">Nieuwe uitnodiging toevoegen</a>
    </section>

    <button @click="saveEvent()">Opslaan</button>
  </div>
</template>

<script>
import Calendar from '../Calendar'
import config from '../../config'

export default {
  name: 'newEvent',
  components: {
    Calendar
  },
  data () {
    return {
      datesToAdd: null,
      userName: 'Timen',
      userEmail: 'timen@gmail.com',
      eventName: 'Evenement',
      eventLocation: 'Locatie',
      eventDescription: 'Omschrijving',
      creator: {
        id: false
      },
      invites: [{
        name: 'test',
        email: 'timen@masoutreach.nl'
      },
      {
        name: 'test2',
        email: 'timen@masoutreach.frl'
      }]
    }
  },
  methods: {
    saveEvent () {
      var creatorId = (this.creator.id) ? this.creator.id : null
      var data = {
        eventCode: false,
        name: this.eventName,
        location: this.eventLocation,
        description: this.eventDescription,
        creator_name: this.userName,
        creator_email: this.userEmail,
        users: this.invites,
        dates: this.datesToAdd[0],
        creatorId: creatorId
      }

      this.$http.post(`${config.rootUrl}/api/new_event.php`, data).then((result) => {
        console.log(result)
        if (result.ok && result.body.length && result.body[0].code && result.body[0].creator_code) {
          this.$router.push({name: 'showEvent', params: { userId: result.body[0].creator_code, eventId: result.body[0].code }})
        }
      }, (e) => {
        console.log(e)
      })
    },
    updateDate (a) {
      this.datesToAdd = a
    },
    addNewInvite () {
      this.invites.push({
        name: '',
        email: ''
      })
    },
    allInvitesValid (invite) {
      let allValid = true // assume all is valid unless the foreach states otherwise

      // When all active invites are valid, auto-add a a new empty entry
      this.invites.forEach(invite => {
        if (invite.name === '' || invite.email === '') {
          allValid = false

          return
        }
      })

      if (allValid) {
        this.addNewInvite()
      }
    },
    removeInvite (invite) {
      let invitationToRemove = this.invites.findIndex(inv => {
        return inv === invite
      })

      if (invitationToRemove) {
        this.invites.splice(invitationToRemove, 1)
      }
    }
  }
}
</script>

