<template>
  <div v-if="mayAccess">
    <span v-if="loading">Laden</span>
    <div class="event" v-else>
      <h1>{{ event.name }}</h1>
      <p>Aangemaakt op {{ event.creation_date }} om {{ event.creation_time }}uur</p>
      <p>Door {{ event.creator_name }}</p>
      <p v-if="event.location">Vindt plaats op: {{ event.location }}</p>
      <p>{{ event.description }}</p>

      <a v-bind:href="overviewUrl" class="btn btn-primary">Bekijk wat alle anderen hebben gestemd</a>
      <a v-bind:href="editUrl" v-if="isCreator" class="btn btn-primary">Wijzig het evenement</a>

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
import config from '../../config'
import moment from 'moment'
moment.locale('nl')

export default {
  name: 'showEvent',
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
    editUrl () {
      return `/#/event/edit/${this.eventId}/${this.userId}`
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
      if (date && date.choiceLoading === false) {
        date.choiceLoading = true
        // date.choice = choice
        /*
        choices:
        1: yes
        2: no
        3: maybe
        0: no choice (Primary state in DB)
        */
        //

        const data = {
          choiceId: date.choiceId,
          choice: choice,
          event_date_id: date.event_date_id,
          event_id: this.eventId,
          user_id: this.userId
        }

        this.$http.post(`${config.rootUrl}/api/save_user_choice.php`, data).then((result) => {
          if (result.ok && result.body.result) {
            date.choice = choice
            date.choiceLoading = false
          }
        }, (e) => {
          console.log(e)
        })
      }
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
<style>
  .selected {
    background: green;
  }
</style>
