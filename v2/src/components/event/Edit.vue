<template>
  <div v-if="mayAccess">
    <span v-if="loading">Laden</span>
    <div class="event" v-else>
      <h1>{{ event.name }}</h1>

      <section class="your-info">
        <div class="form-group">
          <label for="eventName">Naam van het evenement</label>
          <input type="text" v-model="event.name" id="eventName" class="form-control">
        </div>
        <div class="form-group">
          <label for="eventLocation">Locatie</label>
          <input type="text" v-model="event.location" id="eventLocation" class="form-control">
        </div>
        <div class="form-group">
          <label for="eventDescription">Omschrijving van het evenement</label>
          <textarea v-model="event.description" id="eventDescription" class="form-control"></textarea>
        </div>
        <calendar :existingDates="event.dates" @newDateArray="updateDate($event)"></calendar>
      </section>

      <section class="your-info">
        <div class="form-group">
          <label for="userName">Jouw naam</label>
          <input type="text" v-model="event.creator_name" id="userName" class="form-control">
        </div>
        <div class="form-group">
          <label for="userEmail">Jouw e-mailadres</label>
          <input type="text" v-model="event.creator_email" id="userEmail" class="form-control">
        </div>
      </section>

      <a v-bind:href="overviewUrl" class="btn btn-primary">Terug naar het overzicht</a>
    </div>
  </div>
  <div v-else>
    Geen evenement ID of gebruikers ID gevonden
  </div>
</template>

<script>
import Calendar from '../Calendar'
import config from '../../config'
import moment from 'moment'
moment.locale('nl')

export default {
  name: 'editEvent',
  components: {
    Calendar
  },
  data () {
    return {
      eventId: this.$route.params.eventId,
      userId: this.$route.params.userId,
      loading: true,
      isCreator: false,
      event: {
        id: '',
        isCreator: '',
        creator_email: '',
        creator_name: '',
        name: '',
        description: '',
        location: '',
        creation_date: '',
        creation_time: '',
        dates: []
      }
    }
  },
  computed: {
    overviewUrl () {
      return `/#/event/overview/${this.eventId}/${this.userId}`
    },
    mayAccess () {
      return (this.eventId && this.userId)
    }
  },
  created () {
    this.init()
  },
  methods: {
    updateDate (a) {
      this.event.dates = a[0]
    },
    saveEvent () {
      var data = {
        eventCode: false,
        name: this.event.name,
        location: this.event.location,
        description: this.event.description,
        creator_name: this.creator_name,
        creator_email: this.creator_email,
        // users: this.invites,
        dates: this.datesToAdd,
        creatorId: this.userId
      }

      this.$http.post(`${config.rootUrl}/api/edit_event.php`, data).then((result) => {
        console.log(result)
        if (result.ok && result.body.length && result.body[0].code && result.body[0].creator_code) {
          this.$router.push({name: 'showEvent', params: { userId: result.body[0].creator_code, eventId: result.body[0].code }})
        }
      }, (e) => {
        console.log(e)
      })
    },
    init () {
      this.$http.get(`${config.rootUrl}/api/get_event.php?code=${this.eventId}&userCode=${this.userId}`).then((result) => {
        this.loading = false

        if (result.ok && result.body.data) {
          const dataObject = result.body.data
          if (dataObject) {
            dataObject.forEach(data => {
              this.event.id = data.id
              this.event.isCreator = data.isCreator
              this.isCreator = data.isCreator
              this.event.creator_email = data.creator_email
              this.event.creator_name = data.creator_name
              this.event.name = data.name
              this.event.description = data.description
              this.event.location = data.location
              this.event.creation_date = moment(data.creation_date).format('DD MMM')
              this.event.creation_time = moment(data.creation_date).format('kk:mm')
              this.event.dates = []

              if (data.dates) {
                data.dates.forEach(date => this.event.dates.push(date.timestamp))
              }
            })
          }
        } else {
          // no result
          this.loading = false
        }
      }, (e) => {
        console.log(e)
      })
    }
  }
}
</script>
<style>
  .selected {
    background: green;
  }
</style>
