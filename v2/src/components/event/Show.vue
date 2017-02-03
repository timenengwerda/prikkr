<template>
  <div v-if="mayAccess">
    <span v-if="loading">Laden</span>
    <div class="event" v-else>
      <h1>{{ event.name }}</h1>
      <p>Aangemaakt op {{ event.creation_date }} om {{ event.creation_time }}uur</p>
      <p>Door {{ event.creator_name }}</p>
      <p v-if="event.location">Vind plaats op: {{ event.location }}</p>
      <p>{{ event.description }}</p>

      <ul class="dates">
        <li v-for="(dateObj, index) in event.dates">
          <span class="date">{{ dateObj.date }}</span>
          <div class="choices">
            <button class="btn btn-default choiceButton" v-bind:class="{selected: dateObj.choice == 1}" @click="voteForDate(index, 1)">Ja</button>
            <button class="btn btn-default choiceButton" v-bind:class="{selected: dateObj.choice == 2}" @click="voteForDate(index, 2)">Nee</button>
            <button class="btn btn-default choiceButton" v-bind:class="{selected: dateObj.choice == 3}" @click="voteForDate(index, 3)">Misschien</button>
          </div>
          <span v-if="dateObj.choiceLoading">Laden...</span>
        </li>
      </ul>
    </div>
  </div>
  <div v-else>
    Geen evenement ID of gebruikers ID gevonden
  </div>
</template>

<script>
import moment from 'moment'
moment.locale('nl')

export default {
  name: 'showEvent',
  data () {
    return {
      eventId: this.$route.params.eventId,
      userId: this.$route.params.userId,
      loading: true,
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
    isCreator () {
      return true
    },
    mayAccess () {
      return this.eventId && this.userId
    }
  },
  created () {
    this.init()
  },
  methods: {
    voteForDate (dateIndex, choice) {
      let date = this.event.dates[dateIndex]
      if (date) {
        date.choiceLoading = true
        date.choice = choice
      }
    },
    init () {
      this.$http.get(`http://localhost:8888/api/get_event.php?code=${this.eventId}&userCode=${this.userId}`).then((result) => {
        this.loading = false

        if (result.ok && result.body.data) {
          const dataObject = result.body.data
          if (dataObject) {
            dataObject.forEach(data => {
              this.event.id = data.id
              this.event.isCreator = data.isCreator
              this.event.creator_email = data.creator_email
              this.event.creator_name = data.creator_name
              this.event.name = data.name
              this.event.description = data.description
              this.event.location = data.location
              this.event.creation_date = moment(data.creation_date).format('DD MMM')
              this.event.creation_time = moment(data.creation_date).format('kk:mm')
              this.event.dates = []

              if (data.dates) {
                data.dates.forEach(date => {
                  var theDate = moment(date.timestamp)

                  this.event.dates.push({
                    event_date_id: date.event_date_id,
                    choiceId: date.choice.choiceId,
                    date: theDate.format('DD MMM'),
                    choice: date.choice.choice,
                    choiceLoading: false
                  })
                })
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

